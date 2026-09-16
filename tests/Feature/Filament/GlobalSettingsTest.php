<?php

use App\Filament\Pages\GlobalSettings;
use App\Models\Setting;
use App\Services\Cms\Settings;
use Livewire\Livewire;

it('saves global settings to the settings table', function () {
    $this->actingAs(adminUser());

    Livewire::test(GlobalSettings::class)
        ->fillForm([
            'company__name' => 'Markedge Technologies',
            'contact__whatsapp' => '+91 98765 43210',
            'tracking__gtm_container_id' => 'GTM-ABC123',
            'privacy__cookie_banner_enabled' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Setting::valueOf('company.name'))->toBe('Markedge Technologies')
        ->and(Setting::valueOf('contact.whatsapp'))->toBe('+91 98765 43210')
        ->and(Setting::valueOf('tracking.gtm_container_id'))->toBe('GTM-ABC123')
        ->and(Setting::valueOf('privacy.cookie_banner_enabled'))->toBeTrue()
        ->and(app(Settings::class)->get('company.name'))->toBe('Markedge Technologies');
});

it('rejects malformed tracking identifiers', function () {
    $this->actingAs(adminUser());

    Livewire::test(GlobalSettings::class)
        ->fillForm(['company__name' => 'Markedge', 'tracking__gtm_container_id' => 'not-a-container'])
        ->call('save')
        ->assertHasFormErrors(['tracking__gtm_container_id']);
});

it('loads existing values into the form', function () {
    Setting::factory()->create(['key' => 'company.tagline', 'group' => 'company', 'value' => 'Build. Operate. Grow.']);
    $this->actingAs(adminUser());

    Livewire::test(GlobalSettings::class)->assertSchemaStateSet(['company__tagline' => 'Build. Operate. Grow.']);
});

it('is not accessible to sales', function () {
    $this->actingAs(adminUser('Sales'));

    $this->get(GlobalSettings::getUrl())->assertForbidden();
});

it('lets website managers view but hides tracking from them', function () {
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(GlobalSettings::class)->assertOk()->assertSee('Company');
});
