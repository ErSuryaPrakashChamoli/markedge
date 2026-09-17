<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Every scheduled command runs on one server only (cache lock), never overlaps itself, and logs failures.
$guard = fn (Event $event, string $name) => $event
    ->withoutOverlapping(30)
    ->onOneServer()
    ->onFailure(fn () => Log::error("Scheduled command failed: {$name}"));

$guard(Schedule::command('content:publish-scheduled')->everyMinute(), 'content:publish-scheduled');
$guard(Schedule::command('markedge:events-prune')->daily(), 'markedge:events-prune');
$guard(Schedule::command('content:expiring-reminders')->dailyAt('08:00'), 'content:expiring-reminders');
$guard(Schedule::command('markedge:follow-up-reminders')->hourly(), 'markedge:follow-up-reminders');
$guard(Schedule::command('markedge:automation-scan')->hourly(), 'markedge:automation-scan');
$guard(Schedule::command('queue:prune-failed', ['--hours' => 720])->weekly(), 'queue:prune-failed');
$guard(Schedule::command('markedge:search-reindex')->weeklyOn(0, '03:00'), 'markedge:search-reindex');
