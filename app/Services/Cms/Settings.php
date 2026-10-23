<?php

namespace App\Services\Cms;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only access to global settings, loaded once per request and cached by content version.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    /** Content version the memo was loaded for; a bump in the same request reloads. */
    private ?int $loadedVersion = null;

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
        $version = $this->version->current();

        if ($this->loaded === null || $this->loadedVersion !== $version) {
            $this->loadedVersion = $version;
            $this->loaded = Cache::remember(
                $this->version->key('settings:all'),
                now()->addDay(),
                fn (): array => Setting::query()->pluck('value', 'key')->all(),
            );
        }

        return $this->loaded;
    }

    public function logoUrl(): ?string
    {
        return $this->fileUrl('company.logo');
    }

    /**
     * Light logo for the dark footer; null means the footer shows the built-in light logo.
     */
    public function footerLogoUrl(): ?string
    {
        return $this->fileUrl('footer.logo');
    }

    /**
     * Public URL for an image setting stored as a path on the public disk.
     */
    public function fileUrl(string $key): ?string
    {
        $path = $this->get($key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }

    /**
     * Google Maps embed for the contact section: pasted embed code wins, then the map location,
     * then the postal address. Null hides the map.
     */
    public function mapEmbedUrl(): ?string
    {
        if ($source = GoogleMap::embedSource($this->get('contact.map_embed'))) {
            return $source;
        }

        $query = $this->mapQuery();

        return $query !== null ? GoogleMap::embedUrlFor($query) : null;
    }

    /**
     * "Get directions" link; null when only embed code is set, since it carries no searchable place.
     */
    public function mapDirectionsUrl(): ?string
    {
        $query = $this->mapQuery();

        return $query !== null ? GoogleMap::directionsUrlFor($query) : null;
    }

    /**
     * The place searched on Google Maps: the map location, or the address with city and country.
     */
    protected function mapQuery(): ?string
    {
        $location = trim((string) $this->get('contact.map_location'));

        if ($location !== '') {
            return $location;
        }

        $address = collect(preg_split('/\R/', (string) $this->get('contact.address')))
            ->push($this->get('contact.city'), $this->get('contact.country'))
            ->map(fn (mixed $part): string => trim((string) $part, " \t,"))
            ->filter()
            ->unique()
            ->implode(', ');

        return $address !== '' ? $address : null;
    }

    public function forget(): void
    {
        $this->loaded = null;
        $this->loadedVersion = null;
    }
}
