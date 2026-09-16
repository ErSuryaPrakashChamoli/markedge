<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Service;

it('creates a permanent redirect when a published service changes slug', function () {
    $service = Service::factory()->published()->create(['slug' => 'web-dev']);

    $service->update(['slug' => 'web-development']);

    $redirect = Redirect::query()->where('from_path', '/services/web-dev')->first();
    expect($redirect)->not->toBeNull()->and($redirect->to_url)->toBe('/services/web-development')->and($redirect->status_code->value)->toBe(301);

    $this->get('/services/web-dev')->assertStatus(301)->assertRedirect('/services/web-development');
    $this->get('/services/web-development')->assertOk();
});

it('handles pages, products and articles', function () {
    $page = Page::factory()->published()->create(['slug' => 'about-us']);
    $product = Product::factory()->active()->create(['slug' => 'lms']);
    $article = Article::factory()->published()->create(['slug' => 'old-post']);

    $page->update(['slug' => 'about']);
    $product->update(['slug' => 'lead-management-system']);
    $article->update(['slug' => 'new-post']);

    expect(Redirect::query()->where('from_path', '/about-us')->value('to_url'))->toBe('/about')
        ->and(Redirect::query()->where('from_path', '/products/lms')->value('to_url'))->toBe('/products/lead-management-system')
        ->and(Redirect::query()->where('from_path', '/insights/old-post')->value('to_url'))->toBe('/insights/new-post');
});

it('creates nothing for drafts, unchanged slugs or ordinary edits', function () {
    $draft = Service::factory()->create(['slug' => 'draft-slug']);
    $published = Service::factory()->published()->create(['slug' => 'stable']);

    $draft->update(['slug' => 'draft-renamed']);
    $published->update(['name' => 'Renamed service']);
    $published->update(['slug' => 'stable']);

    expect(Redirect::count())->toBe(0);
});

it('repoints earlier redirects and never leaves a loop after successive renames', function () {
    $service = Service::factory()->published()->create(['slug' => 'one']);

    $service->update(['slug' => 'two']);
    $service->update(['slug' => 'three']);
    $service->update(['slug' => 'one']);

    $redirects = Redirect::query()->get()->pluck('to_url', 'from_path')->all();

    expect($redirects)->toBe(['/services/two' => '/services/one', '/services/three' => '/services/one'])
        ->and(Redirect::query()->where('from_path', '/services/one')->exists())->toBeFalse();

    $this->get('/services/two')->assertRedirect('/services/one');
    $this->get('/services/one')->assertOk();
});

it('does not duplicate redirects for the same source', function () {
    $service = Service::factory()->published()->create(['slug' => 'alpha']);

    $service->update(['slug' => 'beta']);
    $service->update(['slug' => 'gamma']);

    expect(Redirect::query()->where('from_path', '/services/alpha')->count())->toBe(1)
        ->and(Redirect::query()->where('from_path', '/services/alpha')->value('to_url'))->toBe('/services/gamma');
});
