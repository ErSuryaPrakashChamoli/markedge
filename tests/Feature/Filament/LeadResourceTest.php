<?php

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use Livewire\Livewire;

it('lists enquiries for sales with status filtering', function () {
    $this->actingAs(adminUser('Sales'));
    $new = Lead::factory()->create(['name' => 'New Person']);
    $spam = Lead::factory()->spam()->create(['name' => 'Spam Person']);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$new, $spam])
        ->filterTable('status', [LeadStatus::New->value])
        ->assertCanSeeTableRecords([$new])
        ->assertCanNotSeeTableRecords([$spam]);
});

it('lets sales update workflow fields without touching attribution', function () {
    $lead = Lead::factory()->create(['first_source' => 'google', 'last_source' => 'linkedin']);
    $this->actingAs(adminUser('Sales'));

    Livewire::test(EditLead::class, ['record' => $lead->getRouteKey()])
        ->fillForm(['status' => LeadStatus::Contacted->value, 'notes' => 'Called back.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Contacted)
        ->notes->toBe('Called back.')
        ->first_source->toBe('google')
        ->last_source->toBe('linkedin');
});

it('shows attribution on the lead detail page', function () {
    $lead = Lead::factory()->create(['first_source' => 'google', 'last_campaign' => 'lms-launch']);
    $this->actingAs(adminUser('Marketing Manager'));

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->assertSee('google')
        ->assertSee('lms-launch')
        ->assertDontSee('Technical');
});

it('shows technical details only to super admins', function () {
    $lead = Lead::factory()->create(['user_agent' => 'Mozilla/5.0 TestAgent']);
    $this->actingAs(adminUser());

    Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])->assertSee('Mozilla/5.0 TestAgent');
});

it('forbids product managers from opening a lead', function () {
    $lead = Lead::factory()->create();
    $this->actingAs(adminUser('Product Manager'));

    $this->get(LeadResource::getUrl('view', ['record' => $lead]))->assertForbidden();
});

it('allows CSV export for marketing managers but not sales', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(adminUser('Marketing Manager'));
    Livewire::test(ListLeads::class)->assertTableBulkActionVisible('exportCsv');

    $this->actingAs(adminUser('Sales'));
    Livewire::test(ListLeads::class)->assertTableBulkActionHidden('exportCsv');
});
