<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Run `php artisan schedule:work` (development) or add this cron entry on
| the server:   * * * * * cd /path-to-project && php artisan schedule:run
|
| pm:run-schedulers does: PM due check & work order generation, overdue
| work order alerts, low-stock alerts, AMC expiry / missed visit alerts,
| compliance & calibration due reminders. It is safe to run repeatedly.
*/
Schedule::command('pm:run-schedulers')->dailyAt('06:00')->withoutOverlapping();

// Running-hour based PM can become due at any time of day, so check more often.
Schedule::command('pm:run-schedulers --only=pm')->everyFourHours()->withoutOverlapping();
