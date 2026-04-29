<?php

/**
 * Add these scheduler entries to your routes/console.php file.
 *
 * Prerequisite: your server must have the Laravel cron entry:
 *   * * * * * cd /var/www/media.lasallesoftware.ca && php artisan schedule:run >> /dev/null 2>&1
 *
 * SEE NEXT COMMENT! 
*/

/* 
══════════════════════════════════════════════════════════════════════════════════════════════════
Run it inside your Docker container, not on the host. Your PHP, Laravel, Composer dependencies, environment variables, and database access all live inside the `media_app` container. A cron on the host wouldn't have access to any of that.

The simplest approach for a single-user app: set up the cron on the host, but have it `docker exec` into the container:

```
* * * * * docker exec media_app php artisan schedule:run >> /dev/null 2>&1
```

Replace `media_app` with whatever your container name is (check with `docker ps`). This keeps cron management on the host where it's easy to inspect, while the actual command runs inside the container where everything is properly configured.


These are the steps:
1) SSH into the server
2) open the CRON editor: `crontab -e`
3) enter this line at the bottom: `* * * * * docker exec media_app php artisan schedule:run >> /dev/null 2>&1`
4) save and exit
5) verify by running: `crontab -l`
══════════════════════════════════════════════════════════════════════════════════════════════════
*/

use Illuminate\Support\Facades\Schedule;

// Run health checks every 15 minutes
Schedule::command('health:check')->everyFifteenMinutes();

// Check for digest lists that are due to run, and dispatch processing 
// every 5 minutes
Schedule::command('processing:dispatch')->everyFiveMinutes();

// Run the database backups to S3 every night at 4:00am
Schedule::command('backup:database')->dailyAt('04:00');