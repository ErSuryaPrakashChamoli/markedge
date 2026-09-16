<?php

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tag;
use App\Seo\IndexabilityResolver;
use App\Services\Cms\Settings;

it('marks a published record indexable with a self canonical, sitemap and schema', function () {
    $service = Service::factory()->published()->create(['slug' => 'seo']);

    $decision = app(IndexabilityResolver::class)->forEntity($service);

    expect($decision->indexable)->toBeTrue()
        ->and($decision->robots())->toBe('index, follow')
        ->and($decision->canonical)->toBe('http://localhost/services/seo')
        ->and($decision->canonicalizedElsewhere)->toBeFalse()
        ->and($decision->inSitemap)->toBeTrue()
        ->and($decision->schemaEligible)->toBeTrue();
});

it('excludes every non-public status from indexing, sitemap and schema', function (string $state) {
    $service = match ($state) {
        'draft' => Service::factory()->create(),
        'review' => Service::factory()->create(['status' => PublishStatus::Review]),
        'scheduled' => Service::factory()->scheduled()->create(),
        'archived' => Service::factory()->archived()->create(),
    };

    $decision = app(IndexabilityResolver::class)->forEntity($service);

    expect($decision->indexable)->toBeFalse()
        ->and($decision->canonical)->toBeNull()
        ->and($decision->inSitemap)->toBeFalse()
        ->and($decision->schemaEligible)->toBeFalse();
})->with(['draft', 'review', 'scheduled', 'archived']);

it('keeps noindex records out of the sitemap and schema but keeps their canonical', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->noindex()->create();

    $decision = app(IndexabilityResolver::class)->forEntity($service);

    expect($decision->indexable)->toBeFalse()
        ->and($decision->robots())->toBe('noindex, follow')
        ->and($decision->canonical)->not->toBeNull()
        ->and($decision->inSitemap)->toBeFalse()
        ->and($decision->schemaEligible)->toBeFalse();
});

it('treats a foreign canonical as canonicalized elsewhere', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['canonical_url' => 'https://example.com/original/']);

    $decision = app(IndexabilityResolver::class)->forEntity($service);

    expect($decision->canonical)->toBe('https://example.com/original')
        ->and($decision->canonicalizedElsewhere)->toBeTrue()
        ->and($decision->indexable)->toBeTrue()
        ->and($decision->inSitemap)->toBeFalse()
        ->and($decision->schemaEligible)->toBeFalse();
});

it('respects the sitemap opt-out while staying indexable', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['include_in_sitemap' => false]);

    $decision = app(IndexabilityResolver::class)->forEntity($service);

    expect($decision->indexable)->toBeTrue()->and($decision->inSitemap)->toBeFalse()->and($decision->schemaEligible)->toBeTrue();
});

it('applies content-type defaults for landing pages, tags and authors', function () {
    $resolver = app(IndexabilityResolver::class);
    $landingPage = LandingPage::factory()->published()->create();
    $tag = Tag::factory()->create();
    $authorWithBio = Author::factory()->create(['bio' => '<p>Writes things.</p>']);
    $authorWithout = Author::factory()->create(['bio' => null]);

    expect($resolver->forEntity($landingPage)->indexable)->toBeFalse()
        ->and($resolver->forEntity($tag)->indexable)->toBeFalse()
        ->and($resolver->forEntity($authorWithBio)->indexable)->toBeTrue()
        ->and($resolver->forEntity($authorWithout)->indexable)->toBeFalse();
});

it('marks previews as noindex without canonical, sitemap or schema', function () {
    $decision = app(IndexabilityResolver::class)->forEntity(Service::factory()->published()->create(), preview: true);

    expect($decision->robots())->toBe('noindex, nofollow, noarchive')
        ->and($decision->canonical)->toBeNull()
        ->and($decision->inSitemap)->toBeFalse()
        ->and($decision->schemaEligible)->toBeFalse();
});

it('turns everything noindex when the environment is not indexable', function () {
    config(['markedge.seo.indexable' => false]);
    $resolver = app(IndexabilityResolver::class);

    expect($resolver->forEntity(Product::factory()->active()->create())->robots())->toBe('noindex, nofollow')
        ->and($resolver->forEntity(Article::factory()->published()->create())->inSitemap)->toBeFalse()
        ->and($resolver->forListing('/services')->indexable)->toBeFalse();
});

it('builds absolute canonicals from configuration rather than the request host', function () {
    $service = Service::factory()->published()->create(['slug' => 'cloud']);

    $decision = app(IndexabilityResolver::class)->forEntity($service);
    expect($decision->canonical)->toBe('http://localhost/services/cloud');

    Setting::factory()->create(['key' => 'seo.canonical_host', 'value' => 'https://www.markedge.example/']);
    app()->forgetInstance(Settings::class);

    expect(app(IndexabilityResolver::class)->forEntity($service)->canonical)->toBe('https://www.markedge.example/services/cloud');
    $this->withHeaders(['Host' => 'evil.example'])->get('/services/cloud')->assertOk()->assertSee('href="https://www.markedge.example/services/cloud"', false)->assertDontSee('evil.example');
});
