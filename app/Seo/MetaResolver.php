<?php

namespace App\Seo;

use App\Models\Article;
use App\Models\Concerns\HasSeo;
use App\Models\SeoMeta;
use App\Services\Cms\PublicUrl;
use App\Services\Cms\Settings;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Builds PageMeta through the fallback chain: entity SEO row → entity content → global settings.
 * No field is ever truncated here; search engines decide how to display snippets.
 */
class MetaResolver
{
    public function __construct(
        private readonly Settings $settings,
        private readonly PublicUrl $urls,
    ) {}

    public function forEntity(Model $entity): PageMeta
    {
        $seo = in_array(HasSeo::class, class_uses_recursive($entity), true) ? $entity->seo : null;
        $naturalTitle = $entity->title ?? $entity->name ?? null;

        $title = filled($seo?->title) ? $seo->title : $this->withSuffix($naturalTitle);
        $description = $this->firstFilled([
            $seo?->description,
            $entity->excerpt ?? null,
            $entity->short_description ?? null,
            $entity->tagline ?? null,
            $this->settings->get('seo.default_description'),
        ]);

        $canonical = filled($seo?->canonical_url) ? $seo->canonical_url : $this->canonicalFor($entity);
        $ogImage = $this->ogImageFor($entity, $seo);

        return new PageMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $this->robotsFor($seo),
            ogTitle: $this->firstFilled([$seo?->og_title, $title]),
            ogDescription: $this->firstFilled([$seo?->og_description, $description]),
            ogImage: $ogImage,
            ogType: $entity instanceof Article ? 'article' : 'website',
            twitterTitle: $this->firstFilled([$seo?->twitter_title, $seo?->og_title, $title]),
            twitterDescription: $this->firstFilled([$seo?->twitter_description, $seo?->og_description, $description]),
            twitterImage: $this->mediaUrl($seo, 'twitter_image') ?? $ogImage,
            publishedTime: isset($entity->published_at) ? $entity->published_at->toIso8601String() : null,
            modifiedTime: isset($entity->updated_at) ? $entity->updated_at->toIso8601String() : null,
        );
    }

    /**
     * Metadata for listing pages that have no entity of their own.
     */
    public function forListing(string $title, ?string $description, string $path, bool $indexable = true): PageMeta
    {
        $description ??= $this->settings->get('seo.default_description');

        return new PageMeta(
            title: $this->withSuffix($title),
            description: $description,
            canonical: $this->absolute($path),
            robots: $this->applyGlobalIndexability($indexable ? 'index, follow' : 'noindex, follow'),
            ogTitle: $this->withSuffix($title),
            ogDescription: $description,
            ogImage: $this->settings->fileUrl('seo.default_og_image'),
            twitterTitle: $this->withSuffix($title),
            twitterDescription: $description,
            twitterImage: $this->settings->fileUrl('seo.default_og_image'),
        );
    }

    public function default(): PageMeta
    {
        $title = $this->settings->get('seo.default_title') ?: $this->settings->get('company.name', config('app.name'));

        return $this->forListing($title, null, '/')->with(['title' => $title, 'ogTitle' => $title, 'twitterTitle' => $title]);
    }

    public function withSuffix(?string $title): string
    {
        if (blank($title)) {
            return (string) ($this->settings->get('seo.default_title') ?: config('app.name'));
        }

        return $title.$this->settings->get('seo.title_suffix', ' | '.config('app.name'));
    }

    public function canonicalFor(Model $entity): ?string
    {
        $path = $this->urls->pathFor($entity);

        return $path === null ? null : $this->absolute($path);
    }

    public function absolute(string $path): string
    {
        $host = rtrim((string) $this->settings->get('seo.canonical_host'), '/');
        $path = '/'.ltrim($path, '/');

        return ($host !== '' ? $host : rtrim(config('app.url'), '/')).($path === '/' ? '' : rtrim($path, '/'));
    }

    protected function robotsFor(?SeoMeta $seo): string
    {
        $index = $seo?->robots_index ?? true;
        $follow = $seo?->robots_follow ?? true;

        return $this->applyGlobalIndexability(($index ? 'index' : 'noindex').', '.($follow ? 'follow' : 'nofollow'));
    }

    /**
     * Staging and local environments never index, regardless of entity settings.
     */
    protected function applyGlobalIndexability(string $robots): string
    {
        return config('markedge.seo.indexable') ? $robots : 'noindex, nofollow';
    }

    protected function ogImageFor(Model $entity, ?SeoMeta $seo): ?string
    {
        if ($url = $this->mediaUrl($seo, 'og_image')) {
            return $url;
        }

        if ($entity instanceof HasMedia) {
            foreach (['hero', 'featured', 'logo'] as $collection) {
                $media = $entity->getFirstMedia($collection);

                if ($media) {
                    return $media->hasGeneratedConversion('og') ? $media->getUrl('og') : $media->getUrl();
                }
            }
        }

        return $this->settings->fileUrl('seo.default_og_image');
    }

    protected function mediaUrl(?SeoMeta $seo, string $collection): ?string
    {
        $media = $seo?->getFirstMedia($collection);

        return $media?->getUrl() ?: null;
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    protected function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return null;
    }
}
