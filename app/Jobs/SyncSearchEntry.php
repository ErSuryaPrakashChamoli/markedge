<?php

namespace App\Jobs;

use App\Search\Contracts\SearchEngine;
use App\Search\SearchIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Re-checks the record's current publication and indexability state when it runs, so a job
 * dispatched before an unpublish can never resurrect the entry.
 */
class SyncSearchEntry implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

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
