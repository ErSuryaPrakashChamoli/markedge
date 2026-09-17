<?php

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Filament\Pages\SalesDashboard;
use App\Filament\Pages\SalesPipeline;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Sales\SalesReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('shows the pipeline board to sales with owner and priority filters, never to editors', function () {
    $rep = adminUser('Sales');
    $other = adminUser('Sales');
    Lead::factory()->create(['name' => 'Mine Urgent', 'assigned_to' => $rep->id, 'priority' => LeadPriority::Urgent, 'status' => LeadStatus::Assigned]);
    Lead::factory()->create(['name' => 'Theirs Normal', 'assigned_to' => $other->id, 'status' => LeadStatus::Contacted]);
    Lead::factory()->converted()->create(['name' => 'Closed Won']);
    Lead::factory()->spam()->create(['name' => 'Spam Card']);

    $this->actingAs($rep);

    Livewire::test(SalesPipeline::class)
        ->assertSee('Mine Urgent')->assertSee('Theirs Normal')->assertDontSee('Closed Won')->assertDontSee('Spam Card')
        ->set('owner', 'mine')->assertSee('Mine Urgent')->assertDontSee('Theirs Normal')
        ->set('owner', 'all')->set('priority', 'urgent')->assertSee('Mine Urgent')->assertDontSee('Theirs Normal');

    $this->actingAs(adminUser('Editor'));
    expect(SalesPipeline::canAccess())->toBeFalse();
    $this->get(SalesPipeline::getUrl())->assertForbidden();
});

it('computes the sales dashboard from stored leads only, with rates and SLA gated on data and config', function () {
    $rep = adminUser('Sales');
    Lead::factory()->count(2)->create(['status' => LeadStatus::New, 'assigned_to' => $rep->id, 'created_at' => now()->subHours(30)]);
    Lead::factory()->create(['status' => LeadStatus::Contacted, 'contacted_at' => now()->subHour(), 'created_at' => now()->subHours(3), 'deal_value' => 1500]);
    Lead::factory()->converted()->create(['assigned_to' => $rep->id, 'closed_at' => now()->subDay()]);
    Lead::factory()->lost('budget')->create(['closed_at' => now()->subDay()]);
    Lead::factory()->lost('budget')->create(['closed_at' => now()->subDay()]);
    Lead::factory()->create(['status' => LeadStatus::Unqualified, 'lost_reason' => 'not_a_fit', 'closed_at' => now()->subDay()]);
    LeadFollowUp::factory()->overdue()->create(['user_id' => $rep->id, 'lead_id' => Lead::query()->where('status', LeadStatus::New)->first()->id]);

    $report = SalesReport::forRange('last_7');

    expect($report->pipeline()['new']['count'])->toBe(2)
        ->and($report->pipeline()['contacted']['count'])->toBe(1)
        ->and($report->outcomes())->toMatchArray(['won' => 1, 'lost' => 2, 'unqualified' => 1, 'closed' => 4, 'win_rate' => 33.3])
        ->and($report->followUps())->toMatchArray(['overdue' => 1])
        ->and($report->lostReasons()->first())->toBe(['label' => 'Budget', 'count' => 2])
        ->and($report->owners()->first())->toMatchArray(['open' => 2, 'overdue' => 1, 'won' => 1])
        ->and($report->enteredValue())->toMatchArray(['leads_with_value' => 1, 'open_leads' => 3, 'total' => '1,500.00', 'currency' => null])
        ->and($report->teams())->toBeEmpty();

    $contact = $report->firstContact();
    expect($contact['configured'])->toBeFalse()->and($contact['breaching'])->toBeNull()->and($contact['average_hours'])->toEqual(2.0);

    config()->set('markedge.sales.sla.first_contact_hours', 24);
    $contact = SalesReport::forRange('last_7')->firstContact();
    expect($contact)->toMatchArray(['configured' => true, 'target_hours' => 24, 'breaching' => 2, 'within_target' => 1, 'contacted_in_range' => 1]);

    // Nothing closed: no win rate.
    Lead::query()->closed()->forceDelete();
    expect(SalesReport::forRange('last_7')->outcomes()['win_rate'])->toBeNull();

    $this->actingAs($rep);
    Livewire::test(SalesDashboard::class)->assertSee('Target: 24 h')->assertSee('Teams NOT CONFIGURED')->assertSee('Overdue follow-ups');
    config()->set('markedge.sales.sla.first_contact_hours', null);
    Livewire::test(SalesDashboard::class)->assertSee('SLA target NOT CONFIGURED');
});

it('runs stage moves, notes and follow-ups from the lead view and enforces the matrix in the edit form', function () {
    $rep = adminUser('Sales');
    $lead = Lead::factory()->create();
    $this->actingAs($rep);

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('moveStage', data: ['status' => LeadStatus::Contacted->value])
        ->assertNotified()
        ->callAction('addNote', data: ['body' => 'Left a voicemail.'])
        ->assertNotified()
        ->callAction('scheduleFollowUp', data: ['type' => 'call', 'due_at' => now()->addDay()->startOfMinute()->toDateTimeString(), 'user_id' => $rep->id, 'note' => 'Call back'])
        ->assertNotified()
        ->assertSee('Left a voicemail.')->assertSee('Moved from New to Contacted')
        ->callAction('completeFollowUp', data: ['follow_up_id' => LeadFollowUp::query()->value('id'), 'outcome' => 'Reached.'])
        ->assertNotified();

    expect($lead->refresh())->status->toBe(LeadStatus::Contacted)->next_follow_up_at->toBeNull()
        ->and($lead->activities()->count())->toBe(4);

    // Closing as lost without a reason, or moving outside the matrix, is rejected before anything changes.
    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('moveStage', data: ['status' => LeadStatus::Lost->value])
        ->assertHasActionErrors(['lost_reason']);
    config()->set('markedge.sales.transitions.contacted', []);
    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('moveStage', data: ['status' => LeadStatus::Qualified->value])
        ->assertHasActionErrors(['status']);
    config()->set('markedge.sales.transitions.contacted', ['qualified', 'requirement_understood', 'unqualified', 'lost']);
    expect($lead->refresh()->status)->toBe(LeadStatus::Contacted);

    // The edit form only offers reachable stages and validates the reason.
    Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->fillForm(['status' => LeadStatus::Lost->value, 'lost_reason' => 'timing', 'priority' => 'high', 'deal_value' => 2500])
        ->call('save')->assertHasNoFormErrors();

    expect($lead->refresh())->status->toBe(LeadStatus::Lost)->lost_reason->toBe('timing')->priority->toBe(LeadPriority::High)
        ->and((float) $lead->deal_value)->toBe(2500.0)->and($lead->closed_at)->not->toBeNull();

    Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->fillForm(['status' => LeadStatus::Converted->value])
        ->call('save')->assertHasFormErrors(['status']);
    expect($lead->refresh()->status)->toBe(LeadStatus::Lost)
        ->and(DB::table('activity_log')->where('subject_id', $lead->id)->where('subject_type', (new Lead)->getMorphClass())->count())->toBeGreaterThan(0);
});
