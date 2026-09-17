<?php

namespace App\Jobs;

use App\Search\Contracts\SearchEngine;
use App\Search\SearchIndexer;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Re-checks the record's current publication and indexability state when it runs, so a job
 * dispatched before an unpublish can never resurrect the entry. Idempotent: running it twice
 * produces the same index row.
 */
class SyncSearchEntry implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> seconds before each retry */
    public array $backoff = [10, 60, 300];

    public int $timeout = 60;

    /** Pending duplicates for the same record collapse into one job; a dispatch during processing queues again. */
    public function uniqueId(): string
    {
        return $this->type.':'.$this->id;
    }

    public function __construct(public string $type, public int $id)
    {
        $this->afterCommit();
    }

    public function handle(SearchIndexer $indexer, SearchEngine $engine): void
    {
        $record = SearchIndexer::resolve($this->type, $this->id);

        if ($record === null) {
            $engine->remove($this->type, $this->id);

            return;
        }

        $indexer->sync($record);
    }
}
