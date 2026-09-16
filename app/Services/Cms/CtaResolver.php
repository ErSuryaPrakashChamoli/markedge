<?php

namespace App\Services\Cms;

use App\Enums\CtaAction;
use App\Models\Article;
use App\Models\Cta;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Turns a CTA definition into a usable href. Contact channels come from settings so
 * templates never hard-code numbers or addresses (architecture §23).
 */
class CtaResolver
{
    /** @var array<string, Cta|null> */
    private array $resolved = [];

    public function __construct(
        private readonly Settings $settings,
        private readonly ContentVersion $version,
    ) {}

    public function byKey(?string $key): ?Cta
    {
        if (blank($key)) {
            return null;
        }

        return $this->resolved[$key] ??= Cta::query()->active()->where('key', $key)->first();
    }

    public function fromSetting(string $settingKey): ?Cta
    {
        return $this->byKey($this->settings->get($settingKey));
    }

    /**
     * Entity CTA → parent CTA → per-type default from settings → global default (architecture §23).
     */
    public function forEntity(Model $entity): ?Cta
    {
        if (method_exists($entity, 'cta') && ($cta = $entity->cta) && $cta->is_active) {
            return $cta;
        }

        if ($entity instanceof Service && ($cta = $entity->category?->cta) && $cta->is_active) {
            return $cta;
        }

        $typeKey = match (true) {
            $entity instanceof Service, $entity instanceof ServiceCategory => 'cta.default_service',
            $entity instanceof Product => 'cta.default_product',
            $entity instanceof Article => 'cta.default_article',
            default => 'cta.default',
        };

        return $this->fromSetting($typeKey) ?? $this->fromSetting('cta.default');
    }

    /**
     * Label + href pairs ready for a view.
     *
     * @return array{primary: ?array{label: string, href: string, external: bool}, secondary: ?array{label: string, href: string, external: bool}}
     */
    public function links(Cta $cta, ?string $entityName = null): array
    {
        $primary = $this->primaryHref($cta, $entityName);
        $secondary = $this->secondaryHref($cta, $entityName);

        return [
            'primary' => $primary ? ['label' => $cta->primary_label, 'href' => $primary, 'external' => $this->isExternal($primary) && ! str_starts_with($primary, url('/'))] : null,
            'secondary' => $secondary && $cta->secondary_label ? ['label' => $cta->secondary_label, 'href' => $secondary, 'external' => $this->isExternal($secondary) && ! str_starts_with($secondary, url('/'))] : null,
        ];
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
