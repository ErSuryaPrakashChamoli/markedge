<?php

use App\Filament\Resources\GrievanceLevels\Pages\ManageGrievanceLevels;
use App\Models\GrievanceLevel;
use Livewire\Livewire;

it('creates a grievance level with its contact persons', function () {
    $this->actingAs(adminUser());

    Livewire::test(ManageGrievanceLevels::class)
        ->callAction('create', data: [
            'name' => 'Grievance Officer',
            'response_time' => 'Within 7 working days',
            'description' => 'If Customer Support has not resolved your complaint.',
            'is_visible' => true,
            'sort_order' => 2,
            'contacts' => [
                ['name' => 'Asha Rawat', 'designation' => 'Grievance Officer', 'email' => 'grievance@markedge.test', 'phone' => '+91 98765 43210', 'address' => null],
                ['name' => 'Vikram Negi', 'designation' => 'Deputy Grievance Officer', 'email' => null, 'phone' => null, 'address' => 'Dehradun'],
            ],
        ])
        ->assertHasNoActionErrors();

    $level = GrievanceLevel::query()->with('contacts')->sole();

    expect($level->name)->toBe('Grievance Officer')
        ->and($level->contacts->pluck('name')->all())->toBe(['Asha Rawat', 'Vikram Negi'])
        ->and($level->contacts->first()->email)->toBe('grievance@markedge.test');
});

it('rejects a contact person with an invalid email', function () {
    $this->actingAs(adminUser());

    Livewire::test(ManageGrievanceLevels::class)
        ->callAction('create', data: [
            'name' => 'Nodal Officer',
            'contacts' => [['name' => 'Asha Rawat', 'email' => 'not-an-email']],
        ])
        ->assertHasActionErrors();

    expect(GrievanceLevel::query()->count())->toBe(0);
});

it('keeps the grievance hierarchy behind the settings permission', function () {
    $this->actingAs(adminUser('Content Manager'));

    $this->get(ManageGrievanceLevels::getUrl())->assertForbidden();
});
