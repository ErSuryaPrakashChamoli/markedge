<?php

namespace App\Listeners;

use App\Enums\AutomationTrigger;
use App\Events\LeadCreated;
use App\Events\Sales\LeadAssigned;
use App\Events\Sales\LeadStageChanged;
use App\Jobs\RunAutomation;
use App\Models\AutomationRule;

/**
 * Bridges domain events to the automation engine. Dispatches nothing when no active rule listens.
 */
class TriggerAutomation
{
    public function handle(LeadCreated|LeadStageChanged|LeadAssigned $event): void
    {
        [$trigger, $occurrence, $context] = match (true) {
            $event instanceof LeadCreated => [AutomationTrigger::LeadCreated, 'created', ['duplicate' => $event->duplicate]],
            $event instanceof LeadStageChanged => [AutomationTrigger::LeadStageChanged, $event->from->value.'>'.$event->to->value.':'.now()->timestamp, ['from' => $event->from->value, 'to' => $event->to->value]],
            default => [AutomationTrigger::LeadAssigned, 'owner:'.($event->ownerId ?? 0).':'.now()->timestamp, ['owner_id' => $event->ownerId]],
        };

        if (! AutomationRule::query()->active()->where('trigger', $trigger)->exists()) {
            return;
        }

        RunAutomation::dispatch($trigger, $event->leadId, $occurrence, $context);
    }
}
