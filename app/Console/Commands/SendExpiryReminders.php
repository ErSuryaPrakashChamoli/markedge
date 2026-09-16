<?php

namespace App\Console\Commands;

use App\Editorial\WorkflowModels;
use App\Events\Content\ContentExpiringSoon;
use Illuminate\Console\Command;

/**
 * Internal reminder before scheduled unpublishing. Each record reminds once.
 */
class SendExpiryReminders extends Command
{
    protected $signature = 'content:expiring-reminders';

    protected $description = 'Notify owners and reviewers about content that unpublishes within the configured window';

    public function handle(): int
    {
        $days = (int) config('markedge.editorial.expiry_reminder_days', 3);
        $count = 0;

        foreach (WorkflowModels::all() as $class) {
            $class::query()->expiringWithin($days)->whereNull('expiry_reminded_at')->chunkById(100, function ($records) use (&$count): void {
                foreach ($records as $record) {
                    $record->forceFill(['expiry_reminded_at' => now()])->saveQuietly();
                    event(ContentExpiringSoon::for($record, ['unpublish_at' => $record->unpublish_at?->format(DATE_ATOM)]));
                    $count++;
                }
            });
        }

        $this->info("{$count} expiry reminder(s) sent.");

        return self::SUCCESS;
    }
}
