<?php

namespace App\Seo;

/**
 * Resolved head metadata for one public page (architecture §15). Immutable and fully escaped by the view.
 */
final readonly class PageMeta
{
    /**
     * @param  array<int, array<string, mixed>>  $schema  JSON-LD graph nodes (filled by the Phase 6 schema engine)
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index, follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        public array $schema = [],
    ) {}

    public function isIndexable(): bool
    {
        return ! str_contains($this->robots, 'noindex');
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }

    public function forPreview(): self
    {
        return $this->with([
            'title' => 'Preview: '.$this->title,
            'canonical' => null,
            'robots' => 'noindex, nofollow, noarchive',
            'schema' => [],
        ]);
    }
}
