<?php

use App\Enums\RedirectStatus;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Service;

it('redirects a missing path with a 301 and a valid Location header', function () {
    Redirect::factory()->create(['from_path' => '/old-page', 'to_url' => '/services']);

    $this->get('/old-page')->assertStatus(301)->assertRedirect('/services')->assertHeader('X-Robots-Tag', 'noindex');
});

it('supports temporary redirects and is case and trailing-slash tolerant', function () {
    Redirect::factory()->create(['from_path' => '/promo', 'to_url' => '/products', 'status_code' => RedirectStatus::Found]);

    $this->get('/Promo/')->assertStatus(302)->assertRedirect('/products');
});

it('does not shadow live routes or records', function () {
    Redirect::factory()->create(['from_path' => '/services', 'to_url' => '/products']);
    Page::factory()->published()->create(['slug' => 'about']);
    Redirect::factory()->create(['from_path' => '/about', 'to_url' => '/products']);

    $this->get('/services')->assertOk();
    $this->get('/about')->assertOk();
});

it('collapses redirect chains to the final destination and stops at the depth limit', function () {
    Redirect::factory()->create(['from_path' => '/a', 'to_url' => '/b']);
    Redirect::factory()->create(['from_path' => '/b', 'to_url' => '/c']);
    Redirect::factory()->create(['from_path' => '/c', 'to_url' => '/services']);

    $this->get('/a')->assertRedirect('/services');
});

it('ignores inactive redirects and counts hits', function () {
    $redirect = Redirect::factory()->create(['from_path' => '/hit-me', 'to_url' => '/services']);
    Redirect::factory()->inactive()->create(['from_path' => '/off', 'to_url' => '/services']);

    $this->get('/hit-me')->assertStatus(301);
    $this->get('/off')->assertNotFound();

    expect($redirect->fresh()->hit_count)->toBe(1)->and($redirect->fresh()->last_hit_at)->not->toBeNull();
});

it('rejects unsafe destinations at the model level', function (string $destination) {
    expect(Redirect::isSafeDestination($destination))->toBeFalse();
    expect(fn () => Redirect::factory()->create(['from_path' => '/x', 'to_url' => $destination]))->toThrow(InvalidArgumentException::class);
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html,hi',
    'vbscript' => 'vbscript:msgbox',
    'protocol relative' => '//evil.example/path',
    'external host' => 'https://evil.example/path',
    'control characters' => "/path\nAllow",
]);

it('accepts internal paths, the site host and allow-listed hosts', function () {
    config(['markedge.redirects.allowed_external_hosts' => ['partner.example']]);

    expect(Redirect::isSafeDestination('/new-page'))->toBeTrue()
        ->and(Redirect::isSafeDestination('http://localhost/products'))->toBeTrue()
        ->and(Redirect::isSafeDestination('https://partner.example/page'))->toBeTrue()
        ->and(Redirect::isSafeDestination('https://other.example/page'))->toBeFalse();
});

it('rejects self redirects at the model level', function () {
    expect(fn () => Redirect::factory()->create(['from_path' => '/same', 'to_url' => '/same/']))->toThrow(InvalidArgumentException::class);
});

it('returns a 410 for archived slugs even when a redirect would match nothing', function () {
    $service = Service::factory()->archived()->create(['slug' => 'retired']);

    $this->get('/services/retired')->assertStatus(410);
});

it('never redirects POST requests or JSON clients', function () {
    Redirect::factory()->create(['from_path' => '/old-page', 'to_url' => '/services']);

    $this->post('/old-page')->assertStatus(405);
    $this->getJson('/old-page')->assertNotFound();
});
