<?php

use App\Models\Form;
use App\Models\LandingPage;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\ContentVersion;
use App\Services\Cms\Settings;
use Illuminate\Support\Facades\Cache;

it('ships the lean bundle on pages without Livewire and the Livewire bundle only where needed', function () {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

    expect($manifest)->toHaveKeys(['resources/js/app.js', 'resources/js/lean.js', 'resources/css/app.css']);

    Service::factory()->published()->create(['slug' => 'seo']);
    $plain = $this->get('/services/seo')->assertOk()->getContent();
    expect($plain)->toContain('/build/'.$manifest['resources/js/lean.js']['file'])
        ->not->toContain('/build/'.$manifest['resources/js/app.js']['file'])
        ->not->toContain('livewireScriptConfig');

    LandingPage::factory()->published()->create(['slug' => 'automation', 'form_id' => Form::factory()->create()->id]);
    $form = $this->get('/lp/automation')->assertOk()->getContent();
    expect($form)->toContain('/build/'.$manifest['resources/js/app.js']['file'])
        ->not->toContain('/build/'.$manifest['resources/js/lean.js']['file'])
        ->toContain('livewireScriptConfig');
});

it('preloads only the critical font weights and marks images with dimensions', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match_all('/<link rel="preload" as="font"[^>]*>/', $html, $preloads);

    expect(count($preloads[0]))->toBeLessThanOrEqual(2);
    expect($html)->toContain('font-display: swap');
});

it('invalidates settings and menus through the content version when they change', function () {
    Setting::query()->updateOrCreate(['key' => 'company.name'], ['group' => 'company', 'type' => 'text', 'value' => 'Markedge One']);
    app(Settings::class)->forget();
    app(ContentVersion::class)->bump();
    $this->get('/')->assertSee('Markedge One');

    Setting::query()->where('key', 'company.name')->firstOrFail()->update(['value' => 'Markedge Two']);
    app(Settings::class)->forget();
    $this->get('/')->assertSee('Markedge Two')->assertDontSee('Markedge One');
});

it('memoises the content version per request instead of re-reading the cache', function () {
    $version = app(ContentVersion::class);
    $first = $version->current();

    Cache::forever(ContentVersion::KEY, $first + 10);
    expect($version->current())->toBe($first);

    $version->forget();
    expect($version->current())->toBe($first + 10);
    expect($version->bump())->toBe($first + 11);
});
