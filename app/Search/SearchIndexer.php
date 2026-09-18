<?php

namespace App\Search;

use App\Models\SearchEntry;
use App\Models\Setting;
use App\Search\Contracts\SearchEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Keeps the search index in step with the CMS. It only ever writes search data.
 */
class SearchIndexer
{
    public function __construct(
        private readonly SearchEngine $engine,
        private readonly SearchDocumentBuilder $documents,
    ) {}

    /**
     * Index the record if it is publicly discoverable, otherwise make sure it is absent.
     */
    public function sync(Model $entity): bool
    {
        $document = $this->documents->forEntity($entity);

        if ($document === null) {
            $this->remove($entity);

            return false;
        }

        $this->engine->index($document);

        return true;
    }

    public function remove(Model $entity): void
    {
        $type = SearchTypes::keyFor($entity);

        if ($type !== null) {
            $this->engine->remove($type, (int) $entity->getKey());
        }
    }

    /**
     * Full rebuild: clears the index, then streams every eligible type in chunks.
     *
     * @param  \Closure(string $type, int $indexed, int $scanned): void|null  $progress
     */
    public function rebuild(int $chunk = 500, ?\Closure $progress = null): int
    {
        $this->engine->removeAll();
        $indexed = 0;

        foreach (SearchTypes::TYPES as $type => $definition) {
            $scanned = 0;
            $count = 0;

            $definition['model']::query()->with(SearchTypes::eagerLoadsFor($type))->chunkById($chunk, function ($records) use (&$indexed, &$scanned, &$count): void {
                foreach ($records as $record) {
                    $scanned++;

                    if ($this->sync($record)) {
                        $indexed++;
                        $count++;
                    }
                }
            });

            if ($progress) {
                $progress($type, $count, $scanned);
            }
        }

        Setting::query()->updateOrCreate(['key' => 'search.last_rebuilt_at'], ['group' => 'system', 'type' => 'text', 'value' => now()->toIso8601String()]);

        return $indexed;
    }

    /**
     * Factual index health: documents per type, discoverable records missing from the index,
     * and index rows whose record is no longer discoverable.
     *
     * @return array{total: int, by_type: array<string, array{indexed: int, discoverable: int, missing: int, stale: int}>, last_rebuilt_at: ?string}
     */
    public function audit(): array
    {
        $stats = $this->engine->stats();
        $byType = [];

        foreach (SearchTypes::TYPES as $type => $definition) {
            $indexedIds = SearchEntry::query()->where('searchable_type', $type)->pluck('searchable_id')->all();
            $discoverableIds = [];

            $definition['model']::query()->with(SearchTypes::eagerLoadsFor($type))->chunkById(500, function ($records) use (&$discoverableIds): void {
                foreach ($records as $record) {
                    if ($this->documents->forEntity($record) !== null) {
                        $discoverableIds[] = (int) $record->getKey();
                    }
                }
            });

            $byType[$type] = [
                'indexed' => count($indexedIds),
                'discoverable' => count($discoverableIds),
                'missing' => count(array_diff($discoverableIds, $indexedIds)),
                'stale' => count(array_diff($indexedIds, $discoverableIds)),
            ];
        }

        return [
            'total' => $stats['total'],
            'by_type' => $byType,
            'last_rebuilt_at' => Setting::valueOf('search.last_rebuilt_at'),
        ];
    }

    /**
     * Resolve a morph alias + id to the current record (including soft-deleted rows so they can be removed).
     */
    public static function resolve(string $type, int $id): ?Model
    {
        $class = Relation::getMorphedModel($type) ?? SearchTypes::modelFor($type);

        if ($class === null) {
            return null;
        }

        $query = $class::query();

        if (method_exists($class, 'withTrashed')) {
            $query->withTrashed();
        }

        return $query->find($id);
    }
}
