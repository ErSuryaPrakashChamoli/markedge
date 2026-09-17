<?php

namespace App\Events\Sales;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised once per follow-up by the reminder command.
 */
class FollowUpDue
{
    use Dispatchable;

    public function __construct(public int $followUpId) {}
}
