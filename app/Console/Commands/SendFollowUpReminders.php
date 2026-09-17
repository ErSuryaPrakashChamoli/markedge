<?php

namespace App\Console\Commands;

use App\Events\Sales\FollowUpDue;
use App\Models\LeadFollowUp;
use Illuminate\Console\Command;

/**
 * Reminds owners about follow-ups due within the configured window (and any overdue ones) once each.
 */
class SendFollowUpReminders extends Command
{
    protected $signature = 'markedge:follow-up-reminders';

    protected $description = 'Notify owners about sales follow-ups that are due soon or overdue';

    public function handle(): int
    {
        $hours = max(1, (int) config('markedge.sales.follow_up_reminder_hours', 24));
        $count = 0;

        LeadFollowUp::query()->dueWithinHours($hours)->whereNull('reminded_at')->whereNotNull('user_id')
            ->chunkById(100, function ($followUps) use (&$count): void {
                foreach ($followUps as $followUp) {
                    $followUp->forceFill(['reminded_at' => now()])->saveQuietly();
                    FollowUpDue::dispatch($followUp->id);
                    $count++;
                }
            });

        $this->info("{$count} follow-up reminder(s) sent.");

        return self::SUCCESS;
    }
}
