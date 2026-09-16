<?php

namespace App\Search;

use Illuminate\Support\Carbon;

/**
 * Normalised representation of one public, indexable record.
 */
final readonly class SearchDocument
{
    /**
     * @param  array<int, string>  $keywords
     */
    public function __construct(
        public string $type,
        public int $id,
        public string $title,
        public ?string $summary,
        public ?string $body,
        public string $path,
        public ?string $categorySlug = null,
        public ?string $categoryLabel = null,
        public array $keywords = [],
        public int $weight = 0,
        public ?Carbon $publishedAt = null,
    ) {}
}
