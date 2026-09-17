<?php

use App\Ai\Contracts\ContentAssistant;
use App\Ai\Contracts\KnowledgeAssistant;
use App\Ai\Contracts\LeadSummarizer;
use App\Ai\Contracts\SalesAssistant;
use App\Ai\UnavailableAssistant;
use App\Automation\AutomationEngine;
use App\Automation\RuleVocabulary;
use App\Enums\AutomationRunStatus;
use App\Enums\AutomationTrigger;
use App\Enums\LeadActivityType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\NotificationDelivery;
use App\Notifications\Channels\ChannelRegistry;
use App\Notifications\Channels\NotificationMessage;
use App\Sales\LeadWorkflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('runs matching rules once per occurrence through the workflow, idempotently', function () {
    $rep = adminUser('Sales');
    $rule = AutomationRule::factory()->create([
        'trigger' => AutomationTrigger::LeadCreated,
        'conditions' => [['field' => 'last_source', 'operator' => 'in', 'value' => ['google', 'linkedin']]],
        'actions' => [
            ['type' => 'set_priority', 'priority' => 'high'],
            ['type' => 'assign_owner', 'user_id' => $rep->id],
            ['type' => 'schedule_follow_up', 'hours' => 4, 'type_of_follow_up' => 'call'],
            ['type' => 'notify', 'channel' => 'database', 'subject' => 'Hot lead {name}', 'body' => 'Enquiry #{id} from {source}'],
        ],
    ]);
    AutomationRule::factory()->create(['trigger' => AutomationTrigger::LeadCreated, 'conditions' => [['field' => 'last_source', 'operator' => 'eq', 'value' => 'bing']], 'actions' => [['type' => 'set_priority', 'priority' => 'urgent']]]);
    $lead = Lead::factory()->create(['last_source' => 'linkedin']);

    $engine = app(AutomationEngine::class);
    $runs = $engine->handle(AutomationTrigger::LeadCreated, $lead, 'created');
    $again = $engine->handle(AutomationTrigger::LeadCreated, $lead, 'created');

    $lead->refresh();

    expect($runs->pluck('status')->map->value->all())->toBe(['succeeded', 'skipped'])
        ->and($again)->toBeEmpty()
        ->and($lead->priority)->toBe(LeadPriority::High)
        ->and($lead->assigned_to)->toBe($rep->id)
        ->and($lead->status)->toBe(LeadStatus::Assigned)
        ->and(LeadFollowUp::query()->where('lead_id', $lead->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $rep->id)->count())->toBeGreaterThanOrEqual(1)
        ->and(NotificationDelivery::query()->where('channel', 'database')->where('status', 'sent')->count())->toBe(1)
        ->and($rule->fresh()->run_count)->toBe(1)
        ->and(AutomationRun::query()->count())->toBe(2)
        ->and($lead->activities()->where('type', LeadActivityType::Assigned)->exists())->toBeTrue();
});

it('records failures without throwing, retries up to the limit and validates the vocabulary', function () {
    $rule = AutomationRule::factory()->create(['trigger' => AutomationTrigger::LeadStageChanged, 'actions' => [['type' => 'assign_owner', 'user_id' => 999999]]]);
    $lead = Lead::factory()->create();

    $run = app(AutomationEngine::class)->handle(AutomationTrigger::LeadStageChanged, $lead, 'x')->first();
    expect($run->status)->toBe(AutomationRunStatus::Succeeded)->and($run->result['actions'][0]['outcome'])->toContain('skipped: user not found');

    // Invalid stored actions surface as a failed run, never as an exception to the caller.
    $rule->forceFill(['actions' => [['type' => 'explode']]])->save();
    $failed = app(AutomationEngine::class)->handle(AutomationTrigger::LeadStageChanged, $lead, 'y')->first();
    expect($failed->status)->toBe(AutomationRunStatus::Failed)->and($failed->attempts)->toBe(1)->and($failed->error)->toContain('Unknown action');

    expect(fn () => RuleVocabulary::validateConditions([['field' => 'password', 'operator' => 'eq', 'value' => 'x']]))->toThrow(ValidationException::class);
    expect(fn () => RuleVocabulary::validateActions([['type' => 'move_stage', 'status' => 'nope']]))->toThrow(ValidationException::class);
});

it('fires from lead events and the scheduled scan, at most once per lead per day', function () {
    $rep = adminUser('Sales');
    AutomationRule::factory()->create(['trigger' => AutomationTrigger::LeadStageChanged, 'conditions' => [['field' => 'status', 'operator' => 'eq', 'value' => 'contacted']], 'actions' => [['type' => 'add_note', 'body' => 'First contact logged']]]);
    $overdueRule = AutomationRule::factory()->create(['trigger' => AutomationTrigger::FollowUpOverdue, 'conditions' => [['field' => 'follow_up_overdue_hours', 'operator' => 'gt', 'value' => 1]], 'actions' => [['type' => 'set_priority', 'priority' => 'urgent']]]);
    AutomationRule::factory()->create(['trigger' => AutomationTrigger::LeadIdle, 'conditions' => [['field' => 'hours_in_stage', 'operator' => 'gte', 'value' => 48], ['field' => 'status', 'operator' => 'in', 'value' => 'new,assigned']], 'actions' => [['type' => 'notify', 'channel' => 'mail', 'subject' => 'Idle lead', 'recipients' => 'sales@example.test']]]);

    $lead = Lead::factory()->create(['assigned_to' => $rep->id]);
    app(LeadWorkflow::class)->transition($lead, LeadStatus::Contacted, $rep);
    expect($lead->activities()->where('type', LeadActivityType::Note)->where('body', 'like', 'Automation:%')->count())->toBe(1);

    $overdue = Lead::factory()->create(['next_follow_up_at' => now()->subHours(3)]);
    $idle = Lead::factory()->create(['stage_entered_at' => now()->subDays(3), 'created_at' => now()->subDays(3)]);

    $this->artisan('markedge:automation-scan')->expectsOutputToContain('automation run(s) evaluated.');
    $this->artisan('markedge:automation-scan');

    expect($overdue->refresh()->priority)->toBe(LeadPriority::Urgent)
        ->and(AutomationRun::query()->where('subject_id', $overdue->id)->where('automation_rule_id', $overdueRule->id)->count())->toBe(1)
        ->and(AutomationRun::query()->where('subject_id', $idle->id)->value('status'))->toBe(AutomationRunStatus::Succeeded)
        ->and(NotificationDelivery::query()->where('channel', 'mail')->value('status'))->toBe('skipped');
});

it('degrades channels safely: mail and webhook report NOT CONFIGURED, webhook posts when configured', function () {
    $registry = app(ChannelRegistry::class);
    $message = new NotificationMessage(subject: 'Test', body: 'Body', recipients: ['ops@example.test'], idempotencyKey: 'k1');

    expect($registry->get('mail')->isConfigured())->toBeFalse()
        ->and($registry->deliver('mail', $message)->status)->toBe('skipped')
        ->and($registry->deliver('webhook', $message)->status)->toBe('skipped')
        ->and($registry->deliver('carrier-pigeon', $message)->status)->toBe('skipped');

    config()->set('markedge.notifications.webhook_url', 'https://hooks.example.test/markedge');
    config()->set('markedge.notifications.webhook_secret', 'secret');
    Http::fake(['hooks.example.test/*' => Http::response(['ok' => true], 200)]);

    $result = $registry->deliver('webhook', new NotificationMessage(subject: 'Hook', body: 'Body', idempotencyKey: 'hook-1'));
    $replay = $registry->deliver('webhook', new NotificationMessage(subject: 'Hook', body: 'Body', idempotencyKey: 'hook-1'));

    expect($result->ok())->toBeTrue()->and($replay->status)->toBe('skipped')->and($replay->reason)->toBe('already delivered');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('X-Markedge-Signature') && $request['subject'] === 'Hook');

    config()->set('markedge.notifications.webhook_url', 'https://down.example.test/markedge');
    Http::fake(['down.example.test/*' => Http::response('down', 503)]);
    expect($registry->deliver('webhook', new NotificationMessage(subject: 'Hook', body: 'Body'))->status)->toBe('failed');
});

it('binds the AI-readiness interfaces to an implementation that reports NOT CONFIGURED', function () {
    $lead = Lead::factory()->create();

    foreach ([LeadSummarizer::class, ContentAssistant::class, SalesAssistant::class, KnowledgeAssistant::class] as $contract) {
        expect(app($contract))->toBeInstanceOf(UnavailableAssistant::class);
    }

    $response = app(LeadSummarizer::class)->summarize($lead);
    expect($response->available)->toBeFalse()->and($response->text)->toBeNull()->and($response->reason)->toContain('NOT CONFIGURED')
        ->and(app(KnowledgeAssistant::class)->answer('What is LMS?')->available)->toBeFalse();
});
