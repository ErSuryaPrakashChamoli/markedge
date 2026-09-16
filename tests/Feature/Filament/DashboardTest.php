<?php

use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\LeadsOverview;
use App\Filament\Widgets\RecentLeads;
use App\Models\Lead;
use App\Models\Page;
use Livewire\Livewire;

it('shows real content counts to a super admin', function () {
    Page::factory()->published()->count(2)->create();
    Page::factory()->create();
    $this->actingAs(adminUser());

    Livewire::test(ContentOverview::class)->assertSee('Published pages')->assertSee('1 drafts');
});

it('shows lead counts and recent enquiries to sales', function () {
    Lead::factory()->create(['name' => 'Recent Enquirer']);
    Lead::factory()->spam()->create(['name' => 'Spam Enquirer']);
    $this->actingAs(adminUser('Sales'));

    Livewire::test(LeadsOverview::class)->assertSee('New enquiries');
    Livewire::test(RecentLeads::class)->assertSee('Recent Enquirer')->assertDontSee('Spam Enquirer');
});

it('hides lead widgets from users without lead access', function () {
    $this->actingAs(adminUser('Content Manager'));

    expect(LeadsOverview::canView())->toBeFalse()
        ->and(RecentLeads::canView())->toBeFalse()
        ->and(ContentOverview::canView())->toBeTrue();
});

it('renders the dashboard and an empty recent-enquiries state when nothing exists', function () {
    $this->actingAs(adminUser());

    $this->get('/admin')->assertOk();
    Livewire::test(ContentOverview::class)->assertSee('Website content');
    Livewire::test(RecentLeads::class)->assertSee('No enquiries yet');
});
