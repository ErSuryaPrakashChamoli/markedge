<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('content:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('markedge:events-prune')->daily()->withoutOverlapping();
Schedule::command('content:expiring-reminders')->dailyAt('08:00')->withoutOverlapping();
