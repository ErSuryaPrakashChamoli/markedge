<?php

use App\Models\Page;
use App\Models\Service;

it('generates a slug from the name when none is given', function () {
    $service = Service::factory()->create(['name' => 'API Development & Integration', 'slug' => null]);

    expect($service->slug)->toBe('api-development-integration');
});

it('keeps an explicitly provided slug', function () {
    $service = Service::factory()->create(['name' => 'Cloud Infrastructure', 'slug' => 'cloud']);

    expect($service->slug)->toBe('cloud');
});

it('suffixes the slug when it collides with an existing record', function () {
    Service::factory()->create(['name' => 'SEO', 'slug' => null]);
    Service::factory()->create(['name' => 'SEO', 'slug' => null]);
    $third = Service::factory()->create(['name' => 'SEO', 'slug' => null]);

    expect($third->slug)->toBe('seo-3');
});

it('does not reuse the slug of a soft-deleted record', function () {
    Service::factory()->create(['slug' => 'networking'])->delete();

    $service = Service::factory()->create(['name' => 'Networking', 'slug' => null]);

    expect($service->slug)->toBe('networking-2');
});

it('does not regenerate the slug when the name changes', function () {
    $service = Service::factory()->create(['name' => 'Old name', 'slug' => null]);

    $service->update(['name' => 'New name']);

    expect($service->fresh()->slug)->toBe('old-name');
});

it('uses the slug as the route key', function () {
    $service = Service::factory()->create(['slug' => 'cybersecurity']);

    expect($service->getRouteKey())->toBe('cybersecurity');
});

it('refuses reserved slugs for pages', function () {
    $page = Page::factory()->create(['title' => 'Services', 'slug' => null]);

    expect($page->slug)->toBe('services-2');
});
