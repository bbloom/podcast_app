<?php

/**
 * Add these scheduler entries to your routes/console.php file.
 *
 * Prerequisite: your server must have the Laravel cron entry:
 *   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
 */

use Illuminate\Support\Facades\Schedule;


// Run the database backup daily at 3:00 AM (server timezone).
// The backup sequence: pg_dump → gzip → S3 upload → integrity check
//   → prune old S3 backups → prune old log rows → write log row
//   → send failure email if needed.
Schedule::command('backup:database')->dailyAt('03:00');