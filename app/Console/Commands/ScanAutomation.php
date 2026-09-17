<?php

namespace App\Console\Commands;

use App\Automation\AutomationEngine;
use App\Enums\AutomationTrigger;
use App\Models\AutomationRule;
use App\Models\Lead;
use Illuminate\Console\Command;

/**
 * Time-based triggers: overdue follow-ups and idle leads. One occurrence per lead per day so a
 * rule acts at most once a day on the same lead.
 */
class ScanAutomation extends Command
{
    protected $signature = 'markedge:automation-scan';

    protected $description = 'Evaluate scheduled automation rules (overdue follow-ups, idle leads)';

    public function handle(AutomationEngine $engine): int
    {
        $count = 0;
        $day = now()->format('Y-m-d');

        if (AutomationRule::query()->active()->where('trigger', AutomationTrigger::FollowUpOverdue)->exists()) {
            Lead::query()->open()->followUpOverdue()->chunkById(200, function ($leads) use ($engine, $day, &$count): void {
                foreach ($leads as $lead) {
                    $count += $engine->handle(AutomationTrigger::FollowUpOverdue, $lead, $day, ['follow_up_overdue_hours' => $lead->next_follow_up_at->diffInMinutes(now()) / 60])->count();
                }
            });
        }

        if (AutomationRule::query()->active()->where('trigger', AutomationTrigger::LeadIdle)->exists()) {
            Lead::query()->open()->chunkById(200, function ($leads) use ($engine, $day, &$count): void {
                foreach ($leads as $lead) {
                    $count += $engine->handle(AutomationTrigger::LeadIdle, $lead, $day)->count();
                }
            });
        }

        $this->info("{$count} automation run(s) evaluated.");

        return self::SUCCESS;
    }
}
