<?php

/**
 * Add these scheduler entries to your routes/console.php file.
 *
 * Prerequisite: your server must have the Laravel cron entry:
 *   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
 */

use Illuminate\Support\Facades\Schedule;

// Run health checks every 15 minutes
Schedule::command('health:check')->everyFifteenMinutes();

// Check for due lists and dispatch processing every 5 minutes
Schedule::command('processing:dispatch')->everyFiveMinutes();
