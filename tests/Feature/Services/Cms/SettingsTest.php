<?php

use App\Models\Setting;
use App\Services\Cms\Settings;

it('returns the stored value or the default', function () {
    Setting::factory()->create(['key' => 'company.name', 'value' => 'Markedge Technologies']);

    $settings = app(Settings::class);

    expect($settings->get('company.name'))->toBe('Markedge Technologies')
        ->and($settings->get('contact.phone', 'none'))->toBe('none')
        ->and($settings->has('contact.phone'))->toBeFalse();
});

it('serves updated values after a setting is saved', function () {
    $setting = Setting::factory()->create(['key' => 'company.name', 'value' => 'Old']);
    expect(app(Settings::class)->get('company.name'))->toBe('Old');

    $setting->update(['value' => 'New']);

    expect(app(Settings::class)->get('company.name'))->toBe('New');
});
