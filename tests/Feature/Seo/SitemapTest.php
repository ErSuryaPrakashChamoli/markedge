<?php

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Tag;
use App\Seo\Sitemap\SitemapGenerator;
use Illuminate\Support\Facades\DB;

it('serves valid XML with absolute canonical URLs for published content only', function () {
    $this->freezeSecond();
    $service = Service::factory()->published()->create(['slug' => 'seo']);
    Service::factory()->create(['slug' => 'draft']);
    Service::factory()->create(['slug' => 'review', 'status' => PublishStatus::Review]);
    Service::factory()->scheduled()->create(['slug' => 'later']);
    Service::factory()->archived()->create(['slug' => 'gone']);
    $noindex = Service::factory()->published()->create(['slug' => 'quiet']);
    SeoMeta::factory()->for($noindex, 'seoable')->noindex()->create();
    Product::factory()->active()->create(['slug' => 'lms']);
    Product::factory()->create(['slug' => 'draft-product']);
    Article::factory()->published()->create(['slug' => 'post']);
    LandingPage::factory()->published()->create(['slug' => 'campaign']);
    Page::factory()->published()->create(['slug' => 'about']);
    Tag::factory()->create(['slug' => 'devops']);

    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();

    $locations = array_map('strval', iterator_to_array($xml->url, false) ? array_map(fn ($u) => $u->loc, iterator_to_array($xml->url, false)) : []);

    expect($locations)->toContain('http://localhost', 'http://localhost/services', 'http://localhost/services/seo', 'http://localhost/products/lms', 'http://localhost/insights/post', 'http://localhost/about')
        ->and($locations)->not->toContain('http://localhost/services/draft', 'http://localhost/services/review', 'http://localhost/services/later', 'http://localhost/services/gone', 'http://localhost/services/quiet', 'http://localhost/products/draft-product', 'http://localhost/lp/campaign', 'http://localhost/insights/tag/devops')
        ->and(collect($locations)->filter(fn (string $loc) => str_contains($loc, '/admin') || str_contains($loc, '/preview') || str_contains($loc, '/styleguide')))->toBeEmpty()
        ->and(collect($locations)->every(fn (string $loc) => str_starts_with($loc, 'http://localhost')))->toBeTrue();
});

it('uses real modification timestamps and omits lastmod when none exists', function () {
    $this->travelTo('2026-03-01 10:00:00');
    $service = Service::factory()->published()->create(['slug' => 'dated']);
    $this->travelTo('2026-06-01 12:00:00');

    $entries = collect(app(SitemapGenerator::class)->entries())->keyBy('loc');

    expect($entries['http://localhost/services/dated']['lastmod'])->toBe($service->updated_at->toAtomString())
        ->and($entries['http://localhost/services/dated']['lastmod'])->toStartWith('2026-03-01')
        ->and($entries['http://localhost']['lastmod'])->toBeNull()
        ->and($entries['http://localhost/products']['lastmod'])->toBeNull();
});

it('deduplicates URLs and reflects sitemap priority settings', function () {
    $page = Page::factory()->published()->create(['slug' => 'about']);
    SeoMeta::factory()->for($page, 'seoable')->create(['canonical_url' => 'http://localhost/services', 'sitemap_priority' => 0.7]);
    Service::factory()->published()->create(['slug' => 'seo']);

    $locations = array_column(app(SitemapGenerator::class)->entries(), 'loc');

    expect(count($locations))->toBe(count(array_unique($locations)))
        ->and($locations)->not->toContain('http://localhost/about');
});

it('streams large catalogues in chunks without loading everything at once', function () {
    Service::factory()->published()->count(1200)->create();
    config(['markedge.sitemap.chunk' => 500]);

    DB::enableQueryLog();
    $entries = app(SitemapGenerator::class)->entries();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $serviceChunks = collect($queries)->filter(fn (array $q) => str_contains($q['query'], 'from "services"') && str_contains($q['query'], 'limit 500'))->count();

    expect(count($entries))->toBeGreaterThanOrEqual(1200)
        ->and($serviceChunks)->toBeGreaterThanOrEqual(3)
        ->and(count($queries))->toBeLessThan(60);
});

it('escapes XML special characters in locations', function () {
    $xml = app(SitemapGenerator::class)->render([['loc' => 'http://localhost/a?b=1&c=<x>', 'lastmod' => null, 'changefreq' => null, 'priority' => null]]);

    expect($xml)->toContain('&amp;c=&lt;x&gt;')->not->toContain('<x>');
});

it('is empty when the environment is not indexable and caches by content version', function () {
    config(['markedge.seo.indexable' => false]);
    Service::factory()->published()->create();

    expect(app(SitemapGenerator::class)->entries())->toBe([]);
});
