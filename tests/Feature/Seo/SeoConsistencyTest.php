<?php

use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Seo\IndexabilityResolver;
use App\Seo\Sitemap\SitemapGenerator;
use App\Services\Cms\PreviewLink;

/**
 * The same decision must drive meta robots, canonical, sitemap and schema (Phase 6 §36).
 */
it('renders index robots, self canonical, sitemap inclusion and schema for an indexable page', function () {
    $service = Service::factory()->published()->create(['slug' => 'software-development', 'name' => 'Software Development']);
    $decision = app(IndexabilityResolver::class)->forEntity($service);
    expect($decision->indexable)->toBeTrue();

    $response = $this->get('/services/software-development')->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="http://localhost/services/software-development">', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Service"', false);

    $locations = array_column(app(SitemapGenerator::class)->entries(), 'loc');
    expect($locations)->toContain('http://localhost/services/software-development');
});

it('renders noindex, keeps the canonical, and excludes sitemap and schema for a noindex page', function () {
    $service = Service::factory()->published()->create(['slug' => 'hidden-service']);
    SeoMeta::factory()->for($service, 'seoable')->noindex()->create();

    $this->get('/services/hidden-service')->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="http://localhost/services/hidden-service">', false)
        ->assertDontSee('application/ld+json', false);

    expect(array_column(app(SitemapGenerator::class)->entries(), 'loc'))->not->toContain('http://localhost/services/hidden-service');
});

it('uses the foreign canonical and drops the page from sitemap and schema when canonicalized elsewhere', function () {
    $page = Page::factory()->published()->create(['slug' => 'duplicate']);
    SeoMeta::factory()->for($page, 'seoable')->create(['canonical_url' => '/original-page']);
    Page::factory()->published()->create(['slug' => 'original-page']);

    $this->get('/duplicate')->assertOk()
        ->assertSee('<link rel="canonical" href="http://localhost/original-page">', false)
        ->assertDontSee('application/ld+json', false);

    $locations = array_column(app(SitemapGenerator::class)->entries(), 'loc');
    expect($locations)->toContain('http://localhost/original-page')->not->toContain('http://localhost/duplicate');
});

it('gives drafts, archived records and previews no public exposure', function () {
    $draft = Service::factory()->create(['slug' => 'draft-service']);
    $archived = Service::factory()->archived()->create(['slug' => 'old-service']);

    $this->get('/services/draft-service')->assertNotFound();
    $this->get('/services/old-service')->assertStatus(410);
    $this->get(app(PreviewLink::class)->for($draft, 1))->assertOk()
        ->assertSee('noindex, nofollow, noarchive', false)
        ->assertDontSee('rel="canonical"', false)
        ->assertDontSee('application/ld+json', false);

    $locations = array_column(app(SitemapGenerator::class)->entries(), 'loc');
    expect($locations)->not->toContain('http://localhost/services/draft-service')->not->toContain('http://localhost/services/old-service');
});
