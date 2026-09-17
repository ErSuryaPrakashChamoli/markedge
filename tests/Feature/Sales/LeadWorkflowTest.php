<?php

use App\Enums\ConversionEventType;
use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Sales\LeadWorkflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('moves a lead through configured stages, stamping contact and close times and the timeline', function () {
    $workflow = app(LeadWorkflow::class);
    $sales = adminUser('Sales');
    $lead = Lead::factory()->create();

    $workflow->transition($lead, LeadStatus::Contacted, $sales);
    $workflow->transition($lead, LeadStatus::Qualified, $sales);
    $workflow->transition($lead, LeadStatus::Proposal, $sales);
    $workflow->transition($lead, LeadStatus::Converted, $sales);

    $lead->refresh();

    expect($lead->status)->toBe(LeadStatus::Converted)
        ->and($lead->contacted_at)->not->toBeNull()
        ->and($lead->closed_at)->not->toBeNull()
        ->and($lead->stage_entered_at)->not->toBeNull()
        ->and($lead->activities()->where('type', LeadActivityType::StageChanged)->count())->toBe(4)
        ->and($lead->activities()->latest('id')->first()->properties)->toMatchArray(['from' => 'proposal', 'to' => 'converted'])
        ->and($lead->events()->where('type', ConversionEventType::LeadQualified->value)->count())->toBe(1)
        ->and($lead->events()->where('type', ConversionEventType::LeadConverted->value)->count())->toBe(1);
});

it('rejects moves outside the configured matrix and lost closes without a reason', function () {
    $workflow = app(LeadWorkflow::class);
    $lead = Lead::factory()->create();

    expect(fn () => $workflow->transition($lead, LeadStatus::Converted))->toThrow(ValidationException::class, 'cannot move from New to Won');
    expect(fn () => $workflow->transition($lead, LeadStatus::Unqualified))->toThrow(ValidationException::class, 'reason is required');

    $workflow->transition($lead, LeadStatus::Unqualified, reason: 'not_a_fit');
    $lead->refresh();

    expect($lead->status)->toBe(LeadStatus::Unqualified)->and($lead->lost_reason)->toBe('not_a_fit')->and($lead->closed_at)->not->toBeNull();

    // Reopening clears the close.
    $workflow->transition($lead, LeadStatus::Contacted);
    expect($lead->refresh())->closed_at->toBeNull()->lost_reason->toBeNull()->status->toBe(LeadStatus::Contacted);

    // Spam is always reachable; the matrix is configuration.
    config()->set('markedge.sales.transitions.contacted', []);
    expect(fn () => $workflow->transition($lead, LeadStatus::Qualified))->toThrow(ValidationException::class);
    $workflow->transition($lead, LeadStatus::Spam);
    expect($lead->refresh()->status)->toBe(LeadStatus::Spam);
});

it('assigns an owner, moves new leads to assigned, notifies the owner and never the actor', function () {
    $workflow = app(LeadWorkflow::class);
    $manager = adminUser('Sales Manager');
    $rep = adminUser('Sales');
    $lead = Lead::factory()->create();

    $workflow->assign($lead, $rep, $manager);
    $lead->refresh();

    expect($lead->assigned_to)->toBe($rep->id)
        ->and($lead->status)->toBe(LeadStatus::Assigned)
        ->and($lead->activities()->where('type', LeadActivityType::Assigned)->exists())->toBeTrue()
        ->and(DB::table('notifications')->where('notifiable_id', $rep->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $manager->id)->count())->toBe(0)
        ->and(DB::table('notifications')->where('notifiable_id', $rep->id)->value('data'))->toContain('assigned enquiry')->not->toContain($lead->email);

    // Self-assignment produces no notification.
    $other = Lead::factory()->create();
    $workflow->assign($other, $rep, $rep);
    expect(DB::table('notifications')->where('notifiable_id', $rep->id)->count())->toBe(1);
});

it('schedules and completes follow-ups, keeping the next due date on the lead and reminding once', function () {
    $workflow = app(LeadWorkflow::class);
    $rep = adminUser('Sales');
    $lead = Lead::factory()->create(['assigned_to' => $rep->id]);

    $later = $workflow->scheduleFollowUp($lead, $rep, ['type' => 'email', 'due_at' => now()->addDays(3), 'note' => 'Send deck']);
    $soon = $workflow->scheduleFollowUp($lead, $rep, ['type' => FollowUpType::Call, 'due_at' => now()->addHours(2)]);

    expect($lead->refresh()->next_follow_up_at->timestamp)->toBe($soon->due_at->timestamp)
        ->and($soon->user_id)->toBe($rep->id)->and($later->type)->toBe(FollowUpType::Email);

    $this->artisan('markedge:follow-up-reminders')->expectsOutputToContain('1 follow-up reminder(s) sent.');
    $this->artisan('markedge:follow-up-reminders')->expectsOutputToContain('0 follow-up reminder(s) sent.');
    expect(DB::table('notifications')->where('notifiable_id', $rep->id)->count())->toBe(1)
        ->and(LeadFollowUp::query()->whereNotNull('reminded_at')->count())->toBe(1);

    $workflow->completeFollowUp($soon, $rep, 'Spoke to them.');
    expect($lead->refresh()->next_follow_up_at->timestamp)->toBe($later->due_at->timestamp)
        ->and($soon->refresh()->completed_by)->toBe($rep->id)
        ->and($lead->activities()->where('type', LeadActivityType::FollowUpCompleted)->exists())->toBeTrue();

    $workflow->completeFollowUp($later, $rep);
    expect($lead->refresh()->next_follow_up_at)->toBeNull();
});

it('stores only configured qualification answers and records notes on the timeline', function () {
    $workflow = app(LeadWorkflow::class);
    $rep = adminUser('Sales');
    $lead = Lead::factory()->create();

    $workflow->qualify($lead, $rep, ['budget' => 'confirmed', 'timeline' => 'bogus', 'unknown_key' => 'x', 'need_summary' => str_repeat('a', 3000)]);
    $workflow->addNote($lead, $rep, '  Called, wants a demo next week.  ');

    $lead->refresh();

    expect($lead->qualification)->toBe(['budget' => 'confirmed', 'need_summary' => str_repeat('a', 2000)])
        ->and($lead->activities()->where('type', LeadActivityType::Note)->value('body'))->toBe('Called, wants a demo next week.')
        ->and(json_encode($lead->activities()->where('type', LeadActivityType::QualificationUpdated)->value('properties')))->toContain('budget')->not->toContain('confirmed');

    expect(fn () => $workflow->addNote($lead, $rep, '   '))->toThrow(ValidationException::class);
});
