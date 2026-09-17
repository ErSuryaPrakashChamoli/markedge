<?php

use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Filament\Pages\ConversionReports;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Service;
use App\Reports\MarketingReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

function analyticsSession(string $visitor, array $paths, ?string $source = null, bool $cta = false): string
{
    $session = (string) Str::uuid();

    foreach ($paths as $index => $path) {
        ConversionEvent::factory()->create(['type' => ConversionEventType::PageViewed, 'path' => $path, 'visitor_id' => $visitor, 'session_id' => $session, 'source' => $source, 'medium' => $source ? 'cpc' : null, 'meta' => $index === 0 ? ['landing' => true] : null, 'created_at' => now()->subMinutes(10 - $index)]);
    }

    if ($cta) {
        ConversionEvent::factory()->create(['type' => ConversionEventType::CtaClicked, 'path' => end($paths), 'visitor_id' => $visitor, 'session_id' => $session, 'created_at' => now()->subMinutes(2)]);
    }

    return $session;
}

it('builds traffic, funnel, paths, trends, interest and content performance from aggregates', function () {
    $service = Service::factory()->published()->create(['name' => 'Cloud', 'slug' => 'cloud']);
    $product = Product::factory()->active()->create(['name' => 'LMS', 'slug' => 'lms']);
    $a = (string) Str::uuid();
    $b = (string) Str::uuid();
    $c = (string) Str::uuid();

    analyticsSession($a, ['/services/cloud', '/request-quote'], 'google', cta: true);
    analyticsSession($b, ['/'], 'linkedin');
    analyticsSession($c, ['/products/lms', '/products/lms', '/request-demo'], null, cta: true);
    ConversionEvent::query()->where('path', '/services/cloud')->update(['entity_type' => 'service', 'entity_id' => $service->id]);
    ConversionEvent::query()->where('path', '/products/lms')->update(['entity_type' => 'product', 'entity_id' => $product->id]);

    $lead = Lead::factory()->create(['visitor_id' => $a, 'service_id' => $service->id, 'submitted_from_url' => '/request-quote', 'first_landing_page' => '/services/cloud', 'status' => LeadStatus::Qualified, 'created_at' => now()->subMinute()]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::LeadCreated, 'lead_id' => $lead->id, 'visitor_id' => $a, 'created_at' => now()->subMinute()]);
    $second = Lead::factory()->create(['visitor_id' => $c, 'product_id' => $product->id, 'submitted_from_url' => '/request-demo', 'status' => LeadStatus::Converted, 'created_at' => now()->subMinute()]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::LeadCreated, 'lead_id' => $second->id, 'visitor_id' => $c, 'created_at' => now()->subMinute()]);

    $report = MarketingReport::forRange('last_7');

    DB::enableQueryLog();
    $traffic = $report->traffic();
    $funnel = $report->funnel();
    $trend = $report->trend('daily');
    $paths = $report->conversionPaths();
    $content = $report->contentPerformance();
    $products = $report->interest('product', 'products', 'product_id');
    $sources = $report->sessionsBySource();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($traffic)->toBe(['page_views' => 6, 'sessions' => 3, 'visitors' => 3, 'landing_sessions' => 3])
        ->and($funnel['sessions']['count'])->toBe(3)
        ->and($funnel['engaged_sessions'])->toBe(['count' => 2, 'rate' => 66.7])
        ->and($funnel['cta_sessions'])->toBe(['count' => 2, 'rate' => 66.7])
        ->and($funnel['leads'])->toBe(['count' => 2, 'rate' => 66.7])
        ->and($funnel['qualified'])->toBe(['count' => 2, 'rate' => 100.0])
        ->and($funnel['converted'])->toBe(['count' => 1, 'rate' => 50.0])
        ->and($trend->sum('page_views'))->toBe(6)->and($trend->sum('leads'))->toBe(2)
        ->and($paths->first())->toBe(['label' => 'view → cta → lead', 'count' => 2])
        ->and($content->firstWhere('label', '/request-quote'))->toMatchArray(['views' => 1, 'cta_clicks' => 1, 'leads' => 1])
        ->and($content->firstWhere('label', '/services/cloud')['influenced'])->toBe(1)
        ->and($products->first())->toBe(['label' => 'LMS', 'views' => 2, 'leads' => 1])
        ->and($sources->first())->toBe(['label' => 'Direct / Unknown', 'count' => 1])
        ->and($queries)->toBeLessThan(30);

    foreach (['weekly', 'monthly', 'quarterly'] as $granularity) {
        expect($report->trend($granularity)->sum('page_views'))->toBe(6, $granularity);
    }
});

it('shows no rates without a denominator', function () {
    $funnel = MarketingReport::forRange('last_7')->funnel();

    expect($funnel['engaged_sessions']['rate'])->toBeNull()->and($funnel['leads']['rate'])->toBeNull()->and($funnel['qualified']['rate'])->toBeNull();
});

it('records qualified and converted lead outcomes as events exactly once', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::New]);

    $lead->update(['status' => LeadStatus::Contacted]);
    $lead->update(['status' => LeadStatus::Qualified]);
    $lead->update(['status' => LeadStatus::Contacted]);
    $lead->update(['status' => LeadStatus::Qualified]);
    $lead->update(['status' => LeadStatus::Converted]);

    expect(ConversionEvent::query()->where('lead_id', $lead->id)->pluck('type')->map->value->sort()->values()->all())->toBe(['lead_converted', 'lead_qualified'])
        ->and(ConversionEvent::query()->where('type', 'lead_qualified')->first()->visitor_id)->toBe($lead->visitor_id)
        ->and($lead->fresh()->first_source)->toBe('google');
});

it('prunes page views on their own shorter retention', function () {
    ConversionEvent::factory()->create(['type' => ConversionEventType::PageViewed, 'created_at' => now()->subDays(120)]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::PageViewed, 'created_at' => now()->subDays(10)]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::CtaClicked, 'created_at' => now()->subDays(120)]);

    $this->artisan('markedge:events-prune')->assertSuccessful()->run();

    expect(ConversionEvent::count())->toBe(2)->and(ConversionEvent::query()->where('type', 'page_viewed')->count())->toBe(1);
});

it('limits the analytics page to marketing roles and hides technical identifiers', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    analyticsSession((string) Str::uuid(), ['/services/cloud'], 'google', cta: true);

    $this->actingAs(adminUser('Marketing Manager'));
    $html = Livewire::test(ConversionReports::class)->assertOk()->assertSee('Funnel')->assertSee('Conversion paths')->assertSee('google / cpc')->html();
    expect($html)->not->toMatch('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/');

    $this->actingAs(adminUser('Sales'));
    expect(ConversionReports::canAccess())->toBeFalse();
});
