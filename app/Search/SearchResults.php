<?php

namespace App\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * A page of results plus the query that produced them (analytics-ready: query, filters, total).
 */
final readonly class SearchResults
{
    /**
     * @param  LengthAwarePaginator<int, SearchResult>  $paginator
     */
    public function __construct(
        public SearchQuery $query,
        public LengthAwarePaginator $paginator,
        public bool $usedAnyTermFallback = false,
    ) {}

    public function total(): int
    {
        return $this->paginator->total();
    }

    public function isEmpty(): bool
    {
        return $this->paginator->total() === 0;
    }

    /**
     * @return array<int, SearchResult>
     */
    public function items(): array
    {
        return $this->paginator->items();
    }
}
