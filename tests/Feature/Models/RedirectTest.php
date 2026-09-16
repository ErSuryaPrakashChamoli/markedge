<?php

use App\Models\Redirect;

it('normalises the source path on save', function (string $input, string $expected) {
    $redirect = Redirect::factory()->create(['from_path' => $input]);

    expect($redirect->from_path)->toBe($expected);
})->with([
    'uppercase' => ['/Old-Page', '/old-page'],
    'trailing slash' => ['/old-page/', '/old-page'],
    'missing leading slash' => ['old-page', '/old-page'],
    'query string stripped' => ['/old-page?utm=1', '/old-page'],
    'root stays root' => ['/', '/'],
]);

it('lists only active redirects in the active scope', function () {
    $active = Redirect::factory()->create();
    Redirect::factory()->inactive()->create();

    expect(Redirect::active()->pluck('id')->all())->toBe([$active->id]);
});
