<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Entries on a lead's sales timeline. Personal data never sits in properties; notes are free text
 * written by sales and belong to the lead record itself.
 */
enum LeadActivityType: string implements HasLabel
{
    case Created = 'created';
    case StageChanged = 'stage_changed';
    case Assigned = 'assigned';
    case Note = 'note';
    case FollowUpScheduled = 'follow_up_scheduled';
    case FollowUpCompleted = 'follow_up_completed';
    case QualificationUpdated = 'qualification_updated';
    case DetailsUpdated = 'details_updated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => 'Enquiry received',
            self::StageChanged => 'Stage changed',
            self::Assigned => 'Owner assigned',
            self::Note => 'Note',
            self::FollowUpScheduled => 'Follow-up scheduled',
            self::FollowUpCompleted => 'Follow-up completed',
            self::QualificationUpdated => 'Qualification updated',
            self::DetailsUpdated => 'Details updated',
        };
    }
}
