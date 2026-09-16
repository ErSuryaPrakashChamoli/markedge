<?php

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\Redirect;
use Livewire\Livewire;

it('creates a redirect with a normalised source path', function () {
    $this->actingAs(adminUser('SEO Manager'));

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/Old-Page/', 'to_url' => '/new-page', 'status_code' => 301, 'is_active' => true])
        ->assertHasNoActionErrors();

    expect(Redirect::query()->where('from_path', '/old-page')->exists())->toBeTrue();
});

it('rejects a redirect that points to itself', function () {
    $this->actingAs(adminUser('SEO Manager'));

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/same', 'to_url' => '/same/', 'status_code' => 301, 'is_active' => true])
        ->assertHasActionErrors(['to_url']);
});

it('rejects a redirect that would create a loop', function () {
    Redirect::factory()->create(['from_path' => '/b', 'to_url' => '/c']);
    Redirect::factory()->create(['from_path' => '/c', 'to_url' => '/a']);
    $this->actingAs(adminUser('SEO Manager'));

    Livewire::test(ManageRedirects::class)
        ->callAction('create', data: ['from_path' => '/a', 'to_url' => '/b', 'status_code' => 301, 'is_active' => true])
        ->assertHasActionErrors(['to_url']);
});

it('detects loops through the model helper', function () {
    Redirect::factory()->create(['from_path' => '/x', 'to_url' => '/y']);

    expect(Redirect::createsLoop('/y', '/x'))->toBeTrue()
        ->and(Redirect::createsLoop('/y', '/z'))->toBeFalse()
        ->and(Redirect::createsLoop('/y', 'https://example.com'))->toBeFalse();
});

it('forbids content managers from managing redirects', function () {
    $this->actingAs(adminUser('Content Manager'));

    $this->get(RedirectResource::getUrl('index'))->assertForbidden();
});
