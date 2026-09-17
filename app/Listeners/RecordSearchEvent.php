<?php

namespace App\Listeners;

use App\Analytics\Analytics;
use App\Enums\ConversionEventType;
use App\Events\SearchPerformed;

/**
 * Stores an aggregate-friendly search event (query, result count, anonymous visitor id).
 * Never throws: search must work even if measurement fails.
 */
class RecordSearchEvent
{
    public function __construct(private readonly Analytics $analytics) {}

    public function handle(SearchPerformed $event): void
    {
        $this->analytics->record(ConversionEventType::SearchPerformed, [
            'path' => '/search',
            'meta' => ['query' => mb_substr(mb_strtolower($event->query->term), 0, 120), 'results' => $event->total, 'type' => $event->query->type],
        ]);
    }
}
