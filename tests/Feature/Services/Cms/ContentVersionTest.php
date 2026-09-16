<?php

use App\Models\Service;
use App\Services\Cms\ContentVersion;

it('starts at version one', function () {
    expect(app(ContentVersion::class)->current())->toBe(1);
});

it('bumps the version when content is saved or deleted', function () {
    $version = app(ContentVersion::class);
    $before = $version->current();

    $service = Service::factory()->create();

    expect($version->current())->toBeGreaterThan($before);

    $afterCreate = $version->current();
    $service->delete();

    expect($version->current())->toBeGreaterThan($afterCreate);
});

it('embeds the version in cache keys', function () {
    $version = app(ContentVersion::class);

    expect($version->key('menu:header'))->toBe('menu:header:v'.$version->current());
});
