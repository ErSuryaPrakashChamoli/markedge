<?php

namespace App\Listeners;

use App\Editorial\EditorialNotifier;
use App\Events\Content\EditorialEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Turns workflow events into internal (database) notifications. Runs queued and never lets a
 * delivery failure reach the publication transaction.
 */
class NotifyEditorialParticipants implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public int $timeout = 60;

    public function __construct(private readonly EditorialNotifier $notifier) {}

    public function handle(EditorialEvent $event): void
    {
        try {
            $this->notifier->notify($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
