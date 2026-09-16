<?php

use App\Enums\ConversionEventType;
use App\Filament\Pages\ConversionReports;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Service;
use App\Reports\ConversionReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('aggregates leads by source, campaign, form, service, product and pages with database grouping', function () {
    $form = Form::factory()->create(['name' => 'Request quote']);
    $service = Service::factory()->published()->create(['name' => 'Cloud']);
    $product = Product::factory()->active()->create(['name' => 'LMS']);
    $campaign = Campaign::factory()->create(['name' => 'Q4 push', 'utm_campaign' => 'q4']);
    Lead::factory()->count(3)->create(['first_source' => 'google', 'first_medium' => 'cpc', 'last_source' => 'linkedin', 'last_medium' => 'social', 'last_campaign' => 'q4', 'campaign_id' => $campaign->id, 'form_id' => $form->id, 'service_id' => $service->id, 'first_landing_page' => '/services/cloud', 'submitted_from_url' => '/request-quote']);
    Lead::factory()->create(['first_source' => null, 'first_medium' => null, 'last_source' => null, 'product_id' => $product->id, 'form_id' => $form->id, 'submitted_from_url' => '/products/lms']);
    Lead::factory()->spam()->create(['first_source' => 'spam']);
    Lead::factory()->create(['created_at' => now()->subDays(60), 'first_source' => 'old']);

    $report = ConversionReport::forRange('last_30');

    DB::enableQueryLog();
    $first = $report->leadsBySourceMedium('first');
    $sections = [
        $report->leadsBySourceMedium('last'), $report->leadsBy('last_campaign', 'No campaign'), $report->leadsByRelation('campaigns', 'campaign_id'),
        $report->leadsByRelation('forms', 'form_id'), $report->leadsByRelation('services', 'service_id'), $report->leadsByRelation('products', 'product_id'),
        $report->leadsBy('first_landing_page', 'Unknown'), $report->leadsBy('submitted_from_url', 'Unknown'),
    ];
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($first->all())->toBe([['label' => 'google / cpc', 'count' => 3], ['label' => 'Direct / Unknown', 'count' => 1]])
        ->and($sections[0]->first())->toBe(['label' => 'linkedin / social', 'count' => 3])
        ->and($sections[1]->all())->toBe([['label' => 'q4', 'count' => 3], ['label' => 'No campaign', 'count' => 1]])
        ->and($sections[2]->all())->toBe([['label' => 'Q4 push', 'count' => 3]])
        ->and($sections[3]->all())->toBe([['label' => 'Request quote', 'count' => 4]])
        ->and($sections[4]->all())->toBe([['label' => 'Cloud', 'count' => 3]])
        ->and($sections[5]->all())->toBe([['label' => 'LMS', 'count' => 1]])
        ->and($sections[6]->first())->toBe(['label' => '/services/cloud', 'count' => 3])
        ->and($sections[7]->first())->toBe(['label' => '/request-quote', 'count' => 3])
        ->and(count($queries))->toBe(9)
        ->and(collect($queries)->every(fn (array $q) => str_contains(strtolower($q['query']), 'group by')))->toBeTrue();
});

it('counts CTA clicks, form submissions, searches and leads preceded by a search', function () {
    $cta = Cta::factory()->create(['name' => 'Request quote']);
    CtaClick::factory()->count(2)->create(['cta_id' => $cta->id, 'path' => '/services/seo']);
    CtaClick::factory()->create(['cta_id' => $cta->id, 'path' => '/', 'created_at' => now()->subDays(40)]);
    $lead = Lead::factory()->create();
    ConversionEvent::factory()->create(['type' => ConversionEventType::SearchPerformed, 'visitor_id' => $lead->visitor_id, 'created_at' => now()->subMinutes(5), 'meta' => ['query' => 'lms', 'results' => 2]]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::SearchPerformed, 'meta' => ['query' => 'lms', 'results' => 2]]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::FormSubmitted, 'lead_id' => $lead->id]);
    Lead::factory()->create();

    $report = ConversionReport::forRange('last_30');

    expect($report->totals())->toBe(['leads' => 2, 'form_submissions' => 1, 'cta_clicks' => 2, 'searches' => 2, 'leads_after_search' => 1])
        ->and($report->ctaClicks()->all())->toBe([['label' => 'Request quote', 'count' => 2]])
        ->and($report->ctaClicksByPage()->first())->toBe(['label' => '/services/seo', 'count' => 2])
        ->and($report->topSearches()->all())->toBe([['label' => 'lms', 'count' => 2]]);
});

it('resolves date ranges in the application timezone', function () {
    $this->travelTo('2026-03-15 10:00:00');
    Lead::factory()->create(['created_at' => '2026-03-15 01:00:00']);
    Lead::factory()->create(['created_at' => '2026-03-14 23:00:00']);
    Lead::factory()->create(['created_at' => '2026-02-20 12:00:00']);

    expect(ConversionReport::forRange('today')->totals()['leads'])->toBe(1)
        ->and(ConversionReport::forRange('yesterday')->totals()['leads'])->toBe(1)
        ->and(ConversionReport::forRange('last_7')->totals()['leads'])->toBe(2)
        ->and(ConversionReport::forRange('this_month')->totals()['leads'])->toBe(2)
        ->and(ConversionReport::forRange('previous_month')->totals()['leads'])->toBe(1)
        ->and(ConversionReport::forRange('custom', '2026-02-01', '2026-02-28')->totals()['leads'])->toBe(1)
        ->and(ConversionReport::forRange('custom', 'not a date', 'also not')->totals()['leads'])->toBe(3)
        ->and(ConversionReport::forRange('nonsense')->totals()['leads'])->toBe(3);
});

it('shows the reports page to business roles only', function () {
    Lead::factory()->create(['first_source' => 'google', 'first_medium' => 'cpc']);

    $this->actingAs(adminUser('Marketing Manager'));
    Livewire::test(ConversionReports::class)->assertOk()->assertSee('google / cpc')->assertSee('no conversion rate')->assertDontSee('Top search terms');
    Livewire::test(ConversionReports::class)->call('setRange', 'today')->assertSet('range', 'today')->call('setRange', 'bogus')->assertSet('range', 'last_30');

    $this->actingAs(adminUser('Super Admin'));
    Livewire::test(ConversionReports::class)->assertSee('Top search terms');

    $this->actingAs(adminUser('Sales'));
    expect(ConversionReports::canAccess())->toBeFalse();
    $this->get(ConversionReports::getUrl())->assertForbidden();
});

it('hides attribution from sales users but shows it to marketing', function () {
    $lead = Lead::factory()->create(['first_source' => 'google', 'first_campaign' => 'secret-campaign']);

    $this->actingAs(adminUser('Sales'));
    $this->get('/admin/leads/'.$lead->id)->assertOk()->assertDontSee('secret-campaign')->assertDontSee('First touch');

    $this->actingAs(adminUser('Marketing Manager'));
    $this->get('/admin/leads/'.$lead->id)->assertOk()->assertSee('secret-campaign')->assertSee('Conversion page')->assertDontSee('submission_token');

    $this->actingAs(adminUser('Editor'));
    $this->get('/admin/leads/'.$lead->id)->assertForbidden();
});

it('prunes old events and clicks but never leads', function () {
    $lead = Lead::factory()->create(['created_at' => now()->subDays(900)]);
    ConversionEvent::factory()->create(['created_at' => now()->subDays(500), 'lead_id' => $lead->id]);
    ConversionEvent::factory()->create();
    CtaClick::factory()->create(['created_at' => now()->subDays(500)]);
    CtaClick::factory()->create();

    $this->artisan('markedge:events-prune')->assertSuccessful()->run();
    $this->artisan('markedge:events-prune', ['--days' => 5])->assertFailed()->run();

    expect(ConversionEvent::count())->toBe(1)->and(CtaClick::count())->toBe(1)->and(Lead::withTrashed()->count())->toBe(1);
});
