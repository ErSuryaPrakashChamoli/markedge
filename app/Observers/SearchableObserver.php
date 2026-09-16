<?php

namespace App\Observers;

use App\Jobs\SyncSearchEntry;
use App\Search\SearchTypes;
use Illuminate\Database\Eloquent\Model;

/**
 * Any change to a discoverable content type schedules an index sync (create, edit, publish,
 * unpublish, archive, slug change, delete, restore).
 */
class SearchableObserver
{
    public function saved(Model $model): void
    {
        $this->dispatch($model);
    }

    public function deleted(Model $model): void
    {
        $this->dispatch($model);
    }

    public function restored(Model $model): void
    {
        $this->dispatch($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->dispatch($model);
    }

    protected function dispatch(Model $model): void
    {
        $type = SearchTypes::keyFor($model);

        if ($type !== null) {
            SyncSearchEntry::dispatch($type, (int) $model->getKey());
        }
    }
}
