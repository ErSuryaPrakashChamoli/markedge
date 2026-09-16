<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complete, deliberately short list of first-party conversion events (Phase 8 §24).
 */
enum ConversionEventType: string implements HasLabel
{
    case FormSubmitted = 'form_submitted';
    case LeadCreated = 'lead_created';
    case CtaClicked = 'cta_clicked';
    case SearchPerformed = 'search_performed';

    public function getLabel(): string
    {
        return match ($this) {
            self::FormSubmitted => 'Form submitted',
            self::LeadCreated => 'Lead created',
            self::CtaClicked => 'CTA clicked',
            self::SearchPerformed => 'Search performed',
        };
    }
}
