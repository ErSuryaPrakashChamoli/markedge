<?php

namespace App\Seo;

use App\Models\Article;
use App\Models\SeoMeta;
use App\Services\Cms\Settings;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Builds PageMeta through the fallback chain: entity SEO row → entity content → global settings.
 * Robots and canonical come from the IndexabilityResolver, never from local rules.
 * No field is ever truncated here; search engines decide how to display snippets.
 */
class MetaResolver
{
    public function __construct(
        private readonly Settings $settings,
        private readonly IndexabilityResolver $indexability,
    ) {}

    public function forEntity(Model $entity, bool $preview = false): PageMeta
    {
        $seo = $this->indexability->seoFor($entity);
        $decision = $this->indexability->forEntity($entity, $preview);
        $naturalTitle = $entity->title ?? $entity->name ?? null;

        $title = filled($seo?->title) ? $seo->title : $this->withSuffix($naturalTitle);
        $description = $this->firstFilled([
            $seo?->description,
            $entity->excerpt ?? null,
            $entity->short_description ?? null,
            $entity->tagline ?? null,
            $this->settings->get('seo.default_description'),
        ]);

        $ogImage = $this->ogImageFor($entity, $seo);

        return new PageMeta(
            title: $preview ? 'Preview: '.$title : $title,
            description: $description,
            canonical: $decision->canonical,
            robots: $decision->robots(),
            ogTitle: $this->firstFilled([$seo?->og_title, $title]),
            ogDescription: $this->firstFilled([$seo?->og_description, $description]),
            ogImage: $ogImage,
            ogType: $entity instanceof Article ? 'article' : 'website',
            twitterTitle: $this->firstFilled([$seo?->twitter_title, $seo?->og_title, $title]),
            twitterDescription: $this->firstFilled([$seo?->twitter_description, $seo?->og_description, $description]),
            twitterImage: $this->mediaUrl($seo, 'twitter_image') ?? $ogImage,
            publishedTime: isset($entity->published_at) ? $entity->published_at->toIso8601String() : null,
            modifiedTime: isset($entity->updated_at) ? $entity->updated_at->toIso8601String() : null,
            indexability: $decision,
        );
    }

    /**
     * Metadata for listing pages that have no entity of their own.
     */
    public function forListing(?string $title, ?string $description, string $path, bool $indexable = true): PageMeta
    {
        $description ??= $this->settings->get('seo.default_description');
        $decision = $this->indexability->forListing($path, $indexable);
        $full = $this->withSuffix($title);

        return new PageMeta(
            title: $full,
            description: $description,
            canonical: $decision->canonical,
            robots: $decision->robots(),
            ogTitle: $full,
            ogDescription: $description,
            ogImage: $this->settings->fileUrl('seo.default_og_image'),
            twitterTitle: $full,
            twitterDescription: $description,
            twitterImage: $this->settings->fileUrl('seo.default_og_image'),
            indexability: $decision,
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
        return $this->indexability->forEntity($entity)->canonical;
    }

    public function absolute(string $path): string
    {
        return $this->indexability->absolute($path);
    }

    public function ogImageFor(Model $entity, ?SeoMeta $seo): ?string
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
