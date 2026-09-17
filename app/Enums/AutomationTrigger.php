<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Events an automation rule can react to. Event triggers fire from the sales/lead events;
 * time triggers are evaluated by the scheduled scan.
 */
enum AutomationTrigger: string implements HasLabel
{
    case LeadCreated = 'lead_created';
    case LeadStageChanged = 'lead_stage_changed';
    case LeadAssigned = 'lead_assigned';
    case FollowUpOverdue = 'follow_up_overdue';
    case LeadIdle = 'lead_idle';

    public function isScheduled(): bool
    {
        return in_array($this, [self::FollowUpOverdue, self::LeadIdle], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::LeadCreated => 'Lead created',
            self::LeadStageChanged => 'Lead stage changed',
            self::LeadAssigned => 'Lead assigned',
            self::FollowUpOverdue => 'Follow-up overdue (scheduled scan)',
            self::LeadIdle => 'Lead idle in stage (scheduled scan)',
        };
    }
}
