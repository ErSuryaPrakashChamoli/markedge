<?php

namespace App\Observers;

use App\Analytics\Analytics;
use App\Attribution\Attribution;
use App\Attribution\Touch;
use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Support\Str;

/**
 * Sales outcomes become analytics events (Phase 13 §8.2) so funnel reporting reaches beyond the lead.
 * Recorded once per status entry; the events carry the lead's own attribution, not the admin's.
 */
class LeadObserver
{
    public function __construct(private readonly Analytics $analytics) {}

    public function updated(Lead $lead): void
    {
        if (! $lead->wasChanged('status')) {
            return;
        }

        $type = match (true) {
            $lead->status === LeadStatus::Qualified => ConversionEventType::LeadQualified,
            $lead->status?->isWon() => ConversionEventType::LeadConverted,
            default => null,
        };

        if ($type === null || $lead->events()->where('type', $type->value)->exists()) {
            return;
        }

        $this->analytics->record($type, [
            'lead_id' => $lead->id,
            'form_id' => $lead->form_id,
            'cta_id' => $lead->cta_id,
            'campaign_id' => $lead->campaign_id,
            'path' => $lead->submitted_from_url,
            'entity_type' => $lead->product_id ? 'product' : ($lead->service_id ? 'service' : null),
            'entity_id' => $lead->product_id ?? $lead->service_id,
        ], null, new Attribution(
            visitorId: $lead->visitor_id ?? (string) Str::uuid(),
            first: null,
            last: $lead->last_source || $lead->last_campaign ? new Touch($lead->last_source, $lead->last_medium, $lead->last_campaign, null, null, null, $lead->last_landing_page ?? '/', now()->toIso8601String()) : null,
        ));
    }
}
