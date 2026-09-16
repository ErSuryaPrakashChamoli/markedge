<?php

namespace App\Search\Contracts;

use App\Search\SearchDocument;
use App\Search\SearchQuery;
use App\Search\SearchResults;

/**
 * The only surface the application uses for search. Swap the binding to move to another engine.
 */
interface SearchEngine
{
    public function search(SearchQuery $query): SearchResults;

    public function index(SearchDocument $document): void;

    public function remove(string $type, int $id): void;

    public function removeAll(): void;

    /**
     * @return array{total: int, by_type: array<string, int>}
     */
    public function stats(): array;
}
