<?php

namespace App\Listeners;

use App\Attribution\AttributionCookie;
use App\Enums\ConversionEventType;
use App\Events\SearchPerformed;
use App\Models\ConversionEvent;
use Throwable;

/**
 * Stores an aggregate-friendly search event (query, result count, anonymous visitor id).
 * Never throws: search must work even if measurement fails.
 */
class RecordSearchEvent
{
    public function __construct(private readonly AttributionCookie $cookie) {}

    public function handle(SearchPerformed $event): void
    {
        try {
            $attribution = $this->cookie->read(request());

            ConversionEvent::query()->create([
                'type' => ConversionEventType::SearchPerformed,
                'path' => '/search',
                'visitor_id' => $attribution->visitorId,
                'source' => $attribution->last?->source,
                'medium' => $attribution->last?->medium,
                'campaign' => $attribution->last?->campaign,
                'meta' => ['query' => mb_substr(mb_strtolower($event->query->term), 0, 120), 'results' => $event->total, 'type' => $event->query->type],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
