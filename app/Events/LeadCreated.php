<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain hook raised after a lead is committed. Listeners (email now; Slack, CRM, WhatsApp
 * later) subscribe here and never run inside the capture transaction.
 */
class LeadCreated
{
    use Dispatchable;

    public function __construct(public int $leadId, public bool $duplicate = false) {}
}
