<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complete, deliberately short list of first-party events (Phase 8 §24, Phase 13 §8.2). Each has a
 * business meaning; page views are the only high-volume type and have their own retention.
 */
enum ConversionEventType: string implements HasLabel
{
    case PageViewed = 'page_viewed';
    case SearchPerformed = 'search_performed';
    case CtaClicked = 'cta_clicked';
    case FormSubmitted = 'form_submitted';
    case LeadCreated = 'lead_created';
    case LeadQualified = 'lead_qualified';
    case LeadConverted = 'lead_converted';

    public function getLabel(): string
    {
        return match ($this) {
            self::PageViewed => 'Page viewed',
            self::SearchPerformed => 'Search performed',
            self::CtaClicked => 'CTA clicked',
            self::FormSubmitted => 'Form submitted',
            self::LeadCreated => 'Lead created',
            self::LeadQualified => 'Lead qualified',
            self::LeadConverted => 'Lead converted',
        };
    }
}
