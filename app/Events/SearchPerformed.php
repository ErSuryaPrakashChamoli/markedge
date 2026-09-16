<?php

namespace App\Events;

use App\Search\SearchQuery;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised for every public search. No listener exists yet; a later analytics phase may subscribe.
 * Carries the query, filters and result count only, never personal data.
 */
class SearchPerformed
{
    use Dispatchable;

    public function __construct(public SearchQuery $query, public int $total) {}
}
