<?php

namespace App\Seo;

use App\Models\Author;
use App\Models\Concerns\HasSeo;
use App\Models\LandingPage;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Services\Cms\PublicUrl;
use App\Services\Cms\Settings;
use Illuminate\Database\Eloquent\Model;

/**
 * Decides, once, whether a page is indexable, where its canonical points, whether it belongs
 * in the sitemap and whether it may carry structured data.
 */
class IndexabilityResolver
{
    public function __construct(
        private readonly Settings $settings,
        private readonly PublicUrl $urls,
    ) {}

    public function forEntity(Model $entity, bool $preview = false): Indexability
    {
        if ($preview) {
            return Indexability::preview();
        }

        if (! $this->urls->isPubliclyVisible($entity)) {
            return Indexability::hidden();
        }

        $path = $this->urls->pathFor($entity);

        if ($path === null) {
            return Indexability::hidden('no public url');
        }

        $self = $this->absolute($path);
        $seo = in_array(HasSeo::class, class_uses_recursive($entity), true) ? $entity->seo : null;

        $index = $seo?->robots_index ?? $this->defaultIndexFor($entity);
        $canonical = filled($seo?->canonical_url) ? $this->normalise($seo->canonical_url) : $self;
        $canonicalized = $canonical !== $self;
        $global = (bool) config('markedge.seo.indexable');
        $indexable = $global && $index;
        // Outside an indexable environment nothing should be followed either.
        $follow = $global && ($seo?->robots_follow ?? true);

        return new Indexability(
            indexable: $indexable,
            follow: $follow,
            canonical: $canonical,
            canonicalizedElsewhere: $canonicalized,
            inSitemap: $indexable && ! $canonicalized && ($seo?->include_in_sitemap ?? true),
            schemaEligible: $indexable && ! $canonicalized,
            reason: match (true) {
                ! $global => 'environment is not indexable',
                ! $index => $seo ? 'noindex set in SEO settings' : 'noindex by default for this content type',
                $canonicalized => 'canonical points elsewhere',
                default => 'indexable',
            },
        );
    }

    /**
     * Listing pages have no SEO row; they are indexable unless the caller says otherwise.
     */
    public function forListing(string $path, bool $indexable = true, bool $inSitemap = true): Indexability
    {
        $global = (bool) config('markedge.seo.indexable');
        $canonical = $this->absolute($path);

        return new Indexability(
            indexable: $global && $indexable,
            follow: $global,
            canonical: $canonical,
            inSitemap: $global && $indexable && $inSitemap,
            schemaEligible: $global && $indexable,
            reason: $global ? ($indexable ? 'indexable' : 'listing marked noindex') : 'environment is not indexable',
        );
    }

    /**
     * Absolute, normalised URL from the configured application URL (never the request host).
     */
    public function absolute(string $path): string
    {
        $host = rtrim((string) $this->settings->get('seo.canonical_host'), '/');
        $base = $host !== '' ? $host : rtrim(config('app.url'), '/');
        $path = '/'.ltrim($path, '/');

        return $base.($path === '/' ? '' : rtrim($path, '/'));
    }

    public function siteUrl(): string
    {
        return $this->absolute('/');
    }

    protected function normalise(string $url): string
    {
        $url = trim($url);

        return str_starts_with($url, '/') ? $this->absolute($url) : rtrim($url, '/');
    }

    protected function defaultIndexFor(Model $entity): bool
    {
        return match (true) {
            $entity instanceof LandingPage, $entity instanceof Tag => false,
            $entity instanceof Author => filled(strip_tags((string) $entity->bio)),
            default => true,
        };
    }

    public function seoFor(Model $entity): ?SeoMeta
    {
        return in_array(HasSeo::class, class_uses_recursive($entity), true) ? $entity->seo : null;
    }
}
