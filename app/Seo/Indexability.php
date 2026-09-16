<?php

namespace App\Seo;

/**
 * The single indexability decision for a URL (architecture §15, Phase 6). Consumed by the
 * meta resolver, the schema engine and the sitemap so they can never disagree.
 */
final readonly class Indexability
{
    public function __construct(
        public bool $indexable,
        public bool $follow = true,
        public ?string $canonical = null,
        public bool $canonicalizedElsewhere = false,
        public bool $inSitemap = false,
        public bool $schemaEligible = false,
        public bool $preview = false,
        public string $reason = '',
        /** Published, not noindex, not canonicalized elsewhere: may appear in on-site search and related content. Independent of the search-engine environment flag so discovery works on staging. */
        public bool $discoverable = false,
    ) {}

    public function robots(): string
    {
        if ($this->preview) {
            return 'noindex, nofollow, noarchive';
        }

        return ($this->indexable ? 'index' : 'noindex').', '.($this->follow ? 'follow' : 'nofollow');
    }

    public static function preview(): self
    {
        return new self(indexable: false, follow: false, preview: true, reason: 'preview');
    }

    public static function hidden(string $reason = 'not published'): self
    {
        return new self(indexable: false, follow: false, reason: $reason);
    }
}
