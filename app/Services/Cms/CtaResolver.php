<?php

namespace App\Services\Cms;

use App\Enums\CtaAction;
use App\Models\Cta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Turns a CTA definition into a usable href. Contact channels come from settings so
 * templates never hard-code numbers or addresses (architecture §23).
 */
class CtaResolver
{
    public function __construct(
        private readonly Settings $settings,
        private readonly ContentVersion $version,
    ) {}

    public function byKey(?string $key): ?Cta
    {
        if (blank($key)) {
            return null;
        }

        return Cache::remember(
            $this->version->key("cta:{$key}"),
            now()->addDay(),
            fn (): ?Cta => Cta::query()->active()->where('key', $key)->first(),
        );
    }

    public function fromSetting(string $settingKey): ?Cta
    {
        return $this->byKey($this->settings->get($settingKey));
    }

    public function primaryHref(Cta $cta, ?string $entityName = null): ?string
    {
        return $this->href($cta->primary_action, $cta->primary_value, $cta->whatsappMessageFor($entityName));
    }

    public function secondaryHref(Cta $cta, ?string $entityName = null): ?string
    {
        if ($cta->secondary_action === null) {
            return null;
        }

        return $this->href($cta->secondary_action, $cta->secondary_value, $cta->whatsappMessageFor($entityName));
    }

    public function whatsappHref(?string $message = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->settings->get('contact.whatsapp'));

        if ($number === '') {
            return null;
        }

        return 'https://wa.me/'.$number.(filled($message) ? '?text='.rawurlencode($message) : '');
    }

    public function phoneHref(): ?string
    {
        $phone = $this->settings->get('contact.phone');

        return filled($phone) ? 'tel:'.preg_replace('/[^\d+]/', '', $phone) : null;
    }

    public function emailHref(): ?string
    {
        $email = $this->settings->get('contact.email');

        return filled($email) ? 'mailto:'.$email : null;
    }

    protected function href(CtaAction $action, ?string $value, ?string $whatsappMessage): ?string
    {
        return match ($action) {
            CtaAction::Url => $value,
            CtaAction::Route => filled($value) && Route::has($value) ? route($value) : ($value ?: '/contact'),
            CtaAction::Form => $value ?: '/contact',
            CtaAction::Whatsapp => $this->whatsappHref($whatsappMessage),
            CtaAction::Phone => $this->phoneHref(),
            CtaAction::Email => $this->emailHref(),
        };
    }

    public function isExternal(?string $href): bool
    {
        return $href !== null && preg_match('/^(https?:|tel:|mailto:)/', $href) === 1;
    }
}
