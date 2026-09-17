<?php

namespace App\Jobs;

use App\Automation\AutomationEngine;
use App\Enums\AutomationTrigger;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs the automation engine for one event, off the request path. Safe to run twice.
 */
class RunAutomation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(public AutomationTrigger $trigger, public int $leadId, public string $occurrence, public array $context = []) {}

    public function handle(AutomationEngine $engine): void
    {
        $lead = Lead::query()->find($this->leadId);

        if ($lead === null) {
            return;
        }

        $engine->handle($this->trigger, $lead, $this->occurrence, $this->context);
    }
}
