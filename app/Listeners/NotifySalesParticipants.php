<?php

namespace App\Listeners;

use App\Events\Sales\FollowUpDue;
use App\Events\Sales\LeadAssigned;
use App\Events\Sales\LeadStageChanged;
use App\Sales\SalesNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Turns sales events into database notifications. Queued; a delivery failure never reaches the
 * workflow transaction.
 */
class NotifySalesParticipants implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public int $timeout = 60;

    public function __construct(private readonly SalesNotifier $notifier) {}

    public function handle(LeadAssigned|LeadStageChanged|FollowUpDue $event): void
    {
        try {
            match (true) {
                $event instanceof LeadAssigned => $this->notifier->leadAssigned($event),
                $event instanceof LeadStageChanged => $this->notifier->stageChanged($event),
                default => $this->notifier->followUpDue($event),
            };
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
