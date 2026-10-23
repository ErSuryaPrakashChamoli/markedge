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
        ->assertSee('id="grievance-redressal"', false)
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

it('shows the grievance note above the levels and contact persons', function () {
    $level = GrievanceLevel::factory()->create(['name' => 'Grievance Officer']);
    GrievanceContact::factory()->for($level, 'level')->create(['name' => 'Asha Rawat']);
    $page = Page::factory()->published()->create(['blocks' => [['type' => 'grievance', 'data' => [
        'heading' => 'Grievance redressal',
        'note' => '<h3>Step 1: Contact us directly</h3><p>We acknowledge every complaint within 2 business days.</p><script>alert(1)</script>',
    ]]]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSeeInOrder(['Grievance redressal', 'Step 1: Contact us directly', 'within 2 business days', 'Level 1', 'Grievance Officer', 'Asha Rawat'])
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('shows the grievance note on its own before any level is added', function () {
    $page = Page::factory()->published()->create(['blocks' => [['type' => 'grievance', 'data' => [
        'heading' => 'Grievance redressal',
        'note' => '<p>Complaints about a specific lender follow that lender\'s own process.</p>',
    ]]]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('Grievance redressal')
        ->assertSee('follow that lender', false)
        ->assertDontSee('Level 1');
});
