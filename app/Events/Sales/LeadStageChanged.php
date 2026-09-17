<?php

namespace App\Events\Sales;

use App\Enums\LeadStatus;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised after a lead moves between pipeline stages (outside the workflow transaction).
 */
class LeadStageChanged
{
    use Dispatchable;

    public function __construct(public int $leadId, public LeadStatus $from, public LeadStatus $to, public ?int $actorId, public ?string $reason = null) {}
}
