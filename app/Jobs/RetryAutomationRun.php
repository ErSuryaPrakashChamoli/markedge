<?php

namespace App\Jobs;

use App\Automation\AutomationEngine;
use App\Enums\AutomationRunStatus;
use App\Models\AutomationRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetryAutomationRun implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $runId) {}

    public function handle(AutomationEngine $engine): void
    {
        $run = AutomationRun::query()->find($this->runId);

        if ($run === null || $run->status !== AutomationRunStatus::Failed || $run->attempts >= AutomationEngine::MAX_ATTEMPTS) {
            return;
        }

        $engine->retry($run);
    }
}
