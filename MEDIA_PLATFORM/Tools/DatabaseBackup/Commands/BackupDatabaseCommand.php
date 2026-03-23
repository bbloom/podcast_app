<?php

// ============================================================================
// MEDIA_PLATFORM/Tools/DatabaseBackup/Commands/BackupDatabaseCommand.php
//
// Artisan command: php artisan backup:database
//
// This command is a thin wrapper around BackupService::run(). All backup
// logic lives in the service — the command's only job is to call the
// service and render the result to the console.
//
// Called by:
//   - The Laravel scheduler (daily at 3:00 AM, see routes/scheduler_entries.php)
//   - The admin "Run Backup Now" button (via DatabaseBackupController)
//   - Manually from the CLI: php artisan backup:database
// ============================================================================

namespace MediaPlatform\Tools\DatabaseBackup\Commands;

use MediaPlatform\Tools\DatabaseBackup\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The Artisan command signature.
     */
    protected $signature = 'backup:database';

    /**
     * Short description shown in `php artisan list`.
     */
    protected $description = 'Run a full database backup: pg_dump → gzip → S3 upload → integrity check → prune old backups.';

    /**
     * Execute the command.
     *
     * Delegates entirely to BackupService::run() and renders the step-by-step
     * result to the console output. Returns the appropriate exit code so that
     * cron monitoring tools can detect failures.
     *
     * @return int  Command::SUCCESS (0) or Command::FAILURE (1)
     */
    public function handle(BackupService $service): int
    {
        $this->info('Starting database backup...');
        $this->newLine();

        // Run the full backup sequence.
        $result = $service->run();

        // Print each step with a status indicator.
        foreach ($result['steps'] as $step) {
            $icon = match ($step['status']) {
                'success' => '<fg=green>✓</>',
                'failure' => '<fg=red>✗</>',
                'skipped' => '<fg=yellow>–</>',
                default   => ' ',
            };

            $this->line("  {$icon}  {$step['label']}: {$step['detail']}");
        }

        $this->newLine();

        if ($result['overall_status'] === 'success') {
            $this->info("Backup completed successfully in {$result['duration_seconds']}s.");
            return Command::SUCCESS;
        }

        $this->error("Backup FAILED after {$result['duration_seconds']}s. See log table or email for details.");
        return Command::FAILURE;
    }
}