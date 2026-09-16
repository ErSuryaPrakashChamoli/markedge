<?php

namespace App\Search;

use Illuminate\Support\Carbon;

/**
 * One public result. Only public-safe fields exist here by design.
 */
final readonly class SearchResult
{
    public function __construct(
        public string $type,
        public string $typeLabel,
        public string $title,
        public ?string $excerpt,
        public string $url,
        public ?string $context = null,
        public ?Carbon $publishedAt = null,
    ) {}
}
