<?php

namespace App\Services\Cms;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only access to global settings, loaded once per request and cached by content version.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    public function __construct(private readonly ContentVersion $version) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return filled($this->all()[$key] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->loaded ??= Cache::remember(
            $this->version->key('settings:all'),
            now()->addDay(),
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    public function logoUrl(): ?string
    {
        return Cache::remember(
            $this->version->key('settings:logo'),
            now()->addDay(),
            fn (): string => Setting::query()->where('key', 'company.logo')->first()?->getFirstMediaUrl('file') ?? '',
        ) ?: null;
    }

    public function forget(): void
    {
        $this->loaded = null;
    }
}
