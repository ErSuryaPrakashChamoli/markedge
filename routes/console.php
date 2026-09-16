<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('content:publish-scheduled')->everyMinute()->withoutOverlapping();
