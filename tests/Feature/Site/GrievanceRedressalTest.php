<?php

use App\Models\GrievanceContact;
use App\Models\GrievanceLevel;
use App\Models\Page;

function grievancePage(): Page
{
    return Page::factory()->published()->create([
        'title' => 'Support',
        'blocks' => [['type' => 'grievance', 'data' => ['heading' => 'Grievance redressal', 'intro' => 'Escalate unresolved complaints.']]],
    ]);
}

it('renders the grievance levels in escalation order with their contact persons', function () {
    GrievanceLevel::factory()->create(['name' => 'Nodal Officer', 'sort_order' => 2]);
    $first = GrievanceLevel::factory()->create(['name' => 'Grievance Officer', 'sort_order' => 1, 'response_time' => 'Within 7 working days']);
    GrievanceContact::factory()->for($first, 'level')->create(['name' => 'Asha Rawat', 'designation' => 'Head of Support', 'email' => 'grievance@markedge.test', 'phone' => '+91 98765 43210']);
    GrievanceLevel::factory()->hidden()->create(['name' => 'Hidden Level', 'sort_order' => 3]);

    $this->get('/'.grievancePage()->slug)
        ->assertOk()
        ->assertSeeInOrder(['Grievance redressal', 'Level 1', 'Grievance Officer', 'Within 7 working days', 'Asha Rawat', 'Head of Support', 'Level 2', 'Nodal Officer'])
        ->assertSee('href="mailto:grievance@markedge.test"', false)
        ->assertSee('href="tel:+919876543210"', false)
        ->assertDontSee('Hidden Level');
});

it('leaves the grievance section out until a level is visible', function () {
    GrievanceLevel::factory()->hidden()->create();

    $this->get('/'.grievancePage()->slug)
        ->assertOk()
        ->assertDontSee('Grievance redressal');
});
