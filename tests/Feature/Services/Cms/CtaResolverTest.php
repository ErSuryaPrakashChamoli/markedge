<?php

use App\Enums\CtaAction;
use App\Models\Cta;
use App\Models\Setting;
use App\Services\Cms\CtaResolver;

it('resolves url actions to their value', function () {
    $cta = Cta::factory()->create(['primary_action' => CtaAction::Url, 'primary_value' => '/contact']);

    expect(app(CtaResolver::class)->primaryHref($cta))->toBe('/contact');
});

it('builds a WhatsApp link with the contextual message', function () {
    Setting::factory()->create(['key' => 'contact.whatsapp', 'value' => '+91 98765 43210']);
    $cta = Cta::factory()->whatsapp()->create();

    expect(app(CtaResolver::class)->primaryHref($cta, 'Software Development'))
        ->toBe('https://wa.me/919876543210?text='.rawurlencode('Hi Markedge, I would like to discuss a Software Development requirement.'));
});

it('returns no WhatsApp link when the number is not configured', function () {
    $cta = Cta::factory()->whatsapp()->create();

    expect(app(CtaResolver::class)->primaryHref($cta))->toBeNull();
});

it('resolves phone and email actions from settings', function () {
    Setting::factory()->create(['key' => 'contact.phone', 'value' => '+91 (22) 1234 5678']);
    Setting::factory()->create(['key' => 'contact.email', 'value' => 'hello@example.test']);
    $phone = Cta::factory()->create(['primary_action' => CtaAction::Phone]);
    $email = Cta::factory()->create(['primary_action' => CtaAction::Email]);

    expect(app(CtaResolver::class)->primaryHref($phone))->toBe('tel:+912212345678')
        ->and(app(CtaResolver::class)->primaryHref($email))->toBe('mailto:hello@example.test');
});

it('looks up active CTAs by key and ignores inactive ones', function () {
    Cta::factory()->create(['key' => 'talk-to-us', 'is_active' => false]);

    expect(app(CtaResolver::class)->byKey('talk-to-us'))->toBeNull();
});

it('returns null secondary href when the CTA has no secondary action', function () {
    $cta = Cta::factory()->create();

    expect(app(CtaResolver::class)->secondaryHref($cta))->toBeNull();
});
