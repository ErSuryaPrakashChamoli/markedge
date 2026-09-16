<?php

use App\Models\Article;
use App\Models\Cta;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tag;

it('falls back from entity SEO to content to global defaults', function () {
    Setting::factory()->create(['key' => 'seo.title_suffix', 'value' => ' | Markedge']);
    Setting::factory()->create(['key' => 'seo.default_description', 'value' => 'Global default description']);
    $withSeo = Service::factory()->published()->create(['name' => 'Cloud', 'short_description' => 'Content description']);
    SeoMeta::factory()->for($withSeo, 'seoable')->create(['title' => 'Custom SEO title', 'description' => 'Custom SEO description']);
    $withoutSeo = Service::factory()->published()->create(['name' => 'Networking', 'short_description' => 'Networking content description']);
    $bare = Service::factory()->published()->create(['name' => 'Bare', 'short_description' => null, 'tagline' => null]);

    $this->get('/services/'.$withSeo->slug)
        ->assertSee('<title>Custom SEO title</title>', false)
        ->assertSee('<meta name="description" content="Custom SEO description">', false);

    $this->get('/services/'.$withoutSeo->slug)
        ->assertSee('<title>Networking | Markedge</title>', false)
        ->assertSee('content="Networking content description"', false);

    $this->get('/services/'.$bare->slug)->assertSee('content="Global default description"', false);
});

it('emits absolute canonical URLs, robots and Open Graph tags', function () {
    $product = Product::factory()->active()->create(['slug' => 'lead-management-system', 'name' => 'Lead Management System', 'short_description' => 'Power every lead.']);

    $this->get('/products/lead-management-system')
        ->assertSee('<link rel="canonical" href="http://localhost/products/lead-management-system">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:url" content="http://localhost/products/lead-management-system">', false)
        ->assertSee('<meta name="twitter:card" content="summary">', false);
});

it('honours noindex and custom canonical settings', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->noindex()->create(['canonical_url' => 'https://example.com/original', 'robots_follow' => false]);

    $this->get('/services/'.$service->slug)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<link rel="canonical" href="https://example.com/original">', false);
});

it('accepts a 220-character SEO title without truncation', function () {
    $title = str_repeat('Long title words ', 13);
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['title' => $title]);

    $this->get('/services/'.$service->slug)->assertSee('<title>'.e($title).'</title>', false);
});

it('marks articles as article type with publication times', function () {
    $article = Article::factory()->published()->create();

    $this->get('/insights/'.$article->slug)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('article:published_time', false);
});

it('keeps landing pages no-index by default and uses a large card when an image exists', function () {
    Setting::factory()->create(['key' => 'seo.default_og_image', 'value' => 'settings/og.jpg']);
    $landingPage = LandingPage::factory()->published()->create(['cta_id' => Cta::factory()->create()->id]);
    SeoMeta::factory()->for($landingPage, 'seoable')->noindex()->create();

    $this->get('/lp/'.$landingPage->slug)
        ->assertSee('content="noindex, follow"', false)
        ->assertSee('og:image" content="http://localhost/storage/settings/og.jpg"', false)
        ->assertSee('summary_large_image', false);
});

it('forces noindex everywhere when the environment is not indexable', function () {
    config(['markedge.seo.indexable' => false]);
    $service = Service::factory()->published()->create();

    $this->get('/services/'.$service->slug)->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('marks tag archives no-index and the insights index indexable', function () {
    $tag = Tag::factory()->create(['slug' => 'devops']);

    $this->get('/insights/tag/devops')->assertSee('content="noindex, follow"', false);
    $this->get('/insights')->assertSee('content="index, follow"', false)->assertSee('href="http://localhost/insights"', false);
});
