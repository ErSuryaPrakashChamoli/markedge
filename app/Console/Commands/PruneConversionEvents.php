<?php

namespace App\Console\Commands;

use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use App\Models\CtaClick;
use Illuminate\Console\Command;

/**
 * Deletes measurement rows older than the retention window. Leads are never touched.
 */
class PruneConversionEvents extends Command
{
    protected $signature = 'markedge:events-prune {--days= : Override markedge.analytics.event_retention_days}';

    protected $description = 'Delete conversion events and CTA clicks older than the retention window (never leads)';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('markedge.analytics.event_retention_days', 400));

        if ($days < 30) {
            $this->error('Retention must be at least 30 days.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $pageViewDays = max(7, (int) config('markedge.analytics.pageview_retention_days', 90));
        $views = ConversionEvent::query()->where('type', ConversionEventType::PageViewed->value)->where('created_at', '<', now()->subDays($pageViewDays))->delete();
        $events = ConversionEvent::query()->where('created_at', '<', $cutoff)->delete();
        $clicks = CtaClick::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$views} page views older than {$pageViewDays} days, {$events} other events and {$clicks} CTA clicks older than {$days} days.");

        return self::SUCCESS;
    }
}
