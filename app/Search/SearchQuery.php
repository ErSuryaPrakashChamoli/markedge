<?php

namespace App\Search;

/**
 * Normalised public search request. Also the unit a later analytics phase can record.
 */
final readonly class SearchQuery
{
    public function __construct(
        public string $term,
        public ?string $type = null,
        public ?string $category = null,
        public int $page = 1,
        public int $perPage = 10,
    ) {}

    public function hasTerm(): bool
    {
        return $this->term !== '';
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return ['q' => $this->term, 'type' => $this->type, 'category' => $this->category, 'page' => $this->page];
    }
}
