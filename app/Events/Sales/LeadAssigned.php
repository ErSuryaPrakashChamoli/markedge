<?php

namespace App\Events\Sales;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised after a lead's owner changes. Carries ids only; listeners load what they need.
 */
class LeadAssigned
{
    use Dispatchable;

    public function __construct(public int $leadId, public ?int $ownerId, public ?int $actorId) {}
}
