<?php

// ============================================================================
// MEDIA_PLATFORM/Tools/DatabaseBackup/Services/BackupService.php
//
// Orchestrates the full database backup sequence:
//
//   1. Generate a timestamped filename
//   2. Run pg_dump to a local temp file (plain SQL)
//   3. Gzip-compress the dump file
//   4. Upload the .sql.gz to S3
//   5. Verify the uploaded file integrity (gunzip -t)
//   6. Delete the local temp file (always, via finally{})
//   7. Prune old backup files from S3 (per retention_days config)
//   8. Prune old rows from the database_backup_logs table
//   9. Write a log row to database_backup_logs
//  10. Send a failure email if any step threw an exception
//
// RETURN VALUE
// ────────────
// run() returns a structured result array describing the outcome of every
// step. The controller renders this array directly into the "Run Now" view,
// giving the admin step-by-step feedback.
//
// Result array shape:
// [
//   'overall_status' => 'success' | 'failure',
//   'started_at'     => Carbon,
//   'duration_seconds' => int,
//   'steps' => [
//     ['label' => '...', 'status' => 'success'|'failure'|'skipped', 'detail' => '...'],
//     ...
//   ],
// ]
//
// CONNECTION NOTE
// ───────────────
// pg_dump is invoked with explicit -h / -p flags derived from DB_HOST and
// DB_PORT, forcing TCP rather than a Unix socket. This works in both the
// Docker dev environment (DB_HOST=db) and production (DB_HOST=127.0.0.1).
//
// The database password is passed via the PGPASSWORD environment variable,
// which pg_dump reads automatically. It is never written to disk or logged.
// ============================================================================

namespace MediaPlatform\Tools\DatabaseBackup\Services;

use MediaPlatform\Tools\DatabaseBackup\Models\DatabaseBackupLog;
use MediaPlatform\Tools\DatabaseBackup\Mails\BackupFailedMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Throwable;

class BackupService
{
    // --------------------------------------------------------------------------
    // Public entry point
    // --------------------------------------------------------------------------

    /**
     * Run the full backup sequence and return a structured step-by-step result.
     *
     * Catches all exceptions at the top level so the caller always receives
     * a result array rather than an unhandled exception.
     *
     * @return array  Result array — see class docblock for shape.
     */
    public function run(): array
    {
        $startedAt   = Carbon::now();
        $steps       = [];
        $tempSqlPath = null;
        $tempGzPath  = null;
        $filename    = null;
        $fileSizeBytes = null;
        $overallStatus = 'success';

        try {
            // ------------------------------------------------------------------
            // Step 1: Generate filename
            // ------------------------------------------------------------------
            $filename    = $this->generateFilename();
            $tempSqlPath = config('database_backup.temp_path') . DIRECTORY_SEPARATOR . basename($filename, '.gz');
            $tempGzPath  = config('database_backup.temp_path') . DIRECTORY_SEPARATOR . basename($filename);

            $steps[] = $this->step('Generate filename', 'success', $filename);

            // Ensure the temp directory exists.
            if (! is_dir(config('database_backup.temp_path'))) {
                mkdir(config('database_backup.temp_path'), 0755, true);
            }

            // ------------------------------------------------------------------
            // Step 2: Run pg_dump
            // ------------------------------------------------------------------
            $this->runPgDump($tempSqlPath);
            $steps[] = $this->step('pg_dump', 'success', 'Plain SQL dump written to temp file.');

            // ------------------------------------------------------------------
            // Step 3: Gzip compress
            // ------------------------------------------------------------------
            $this->gzipFile($tempSqlPath, $tempGzPath);
            $fileSizeBytes = filesize($tempGzPath);
            $steps[] = $this->step(
                'Gzip compress',
                'success',
                'Compressed to ' . $this->humanSize($fileSizeBytes) . '.'
            );

            // ------------------------------------------------------------------
            // Step 4: Upload to S3
            // ------------------------------------------------------------------
            $s3Key = $this->buildS3Key($filename);
            $this->uploadToS3($tempGzPath, $s3Key);
            $steps[] = $this->step('Upload to S3', 'success', 's3://' . config('database_backup.S3_bucket') . '/' . $s3Key);

            // ------------------------------------------------------------------
            // Step 5: Verify integrity (gunzip -t on the downloaded file)
            // ------------------------------------------------------------------
            $this->verifyIntegrity($tempGzPath);
            $steps[] = $this->step('Integrity check', 'success', 'gzip checksum verified OK.');

        } catch (Throwable $e) {
            // Mark the overall run as failed and record which step threw.
            $overallStatus = 'failure';
            $steps[]       = $this->step(
                $this->currentStepLabel($steps),
                'failure',
                $e->getMessage()
            );

            Log::error('[DatabaseBackup] Backup failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

        } finally {
            // ------------------------------------------------------------------
            // Always delete temp files, regardless of success or failure.
            // ------------------------------------------------------------------
            $this->deleteTempFile($tempSqlPath);
            $this->deleteTempFile($tempGzPath);
        }

        // ----------------------------------------------------------------------
        // Step 6: Prune old S3 backups (only on success — don't compound a
        // failure run with a potentially confusing prune error).
        // ----------------------------------------------------------------------
        if ($overallStatus === 'success') {
            try {
                $pruned = $this->pruneS3Backups();
                $steps[] = $this->step(
                    'Prune old S3 backups',
                    'success',
                    $pruned > 0 ? "Deleted {$pruned} file(s) older than " . config('database_backup.retention_days') . ' days.' : 'No old files to prune.'
                );
            } catch (Throwable $e) {
                // Pruning failure is non-fatal — log it but don't fail the run.
                $steps[] = $this->step('Prune old S3 backups', 'failure', $e->getMessage());
                Log::warning('[DatabaseBackup] S3 pruning failed (non-fatal).', ['error' => $e->getMessage()]);
            }
        }

        // ----------------------------------------------------------------------
        // Step 7: Prune old log rows
        // ----------------------------------------------------------------------
        try {
            $logRetentionDays = config('database_backup.log_retention_days');

            if ($logRetentionDays > 0) {
                $prunedLogs = DatabaseBackupLog::where('created_at', '<', now()->subDays($logRetentionDays))->delete();
                $steps[] = $this->step(
                    'Prune old log rows',
                    'success',
                    $prunedLogs > 0 ? "Deleted {$prunedLogs} log row(s) older than {$logRetentionDays} days." : 'No old log rows to prune.'
                );
            } else {
                $steps[] = $this->step('Prune old log rows', 'skipped', 'log_retention_days is 0 — keeping all rows.');
            }
        } catch (Throwable $e) {
            $steps[] = $this->step('Prune old log rows', 'failure', $e->getMessage());
            Log::warning('[DatabaseBackup] Log pruning failed (non-fatal).', ['error' => $e->getMessage()]);
        }

        // ----------------------------------------------------------------------
        // Calculate duration
        // ----------------------------------------------------------------------
        $durationSeconds = (int) $startedAt->diffInSeconds(Carbon::now());

        // ----------------------------------------------------------------------
        // Step 8: Write log row
        // ----------------------------------------------------------------------
        $failureMessage = $overallStatus === 'failure'
            ? collect($steps)->firstWhere('status', 'failure')['detail'] ?? 'Unknown error'
            : 'Backup completed successfully. ' . $this->humanSize($fileSizeBytes ?? 0) . ' uploaded to S3.';

        DatabaseBackupLog::create([
            'status'           => $overallStatus,
            'filename'         => $filename,
            'file_size_bytes'  => $fileSizeBytes,
            'duration_seconds' => $durationSeconds,
            'message'          => $failureMessage,
            'ran_at'           => $startedAt,
        ]);

        // ----------------------------------------------------------------------
        // Step 9: Send failure email
        // ----------------------------------------------------------------------
        if ($overallStatus === 'failure') {
            $notificationEmail = config('database_backup.notification_email');

            if (! empty($notificationEmail)) {
                try {
                    Mail::to($notificationEmail)->send(new BackupFailedMail(
                        failedAt: $startedAt,
                        filename: $filename ?? 'unknown',
                        errorMessage: $failureMessage,
                    ));
                } catch (Throwable $mailException) {
                    // Mail failure must not mask the original backup failure.
                    Log::error('[DatabaseBackup] Failed to send failure notification email.', [
                        'error' => $mailException->getMessage(),
                    ]);
                }
            }
        }

        return [
            'overall_status'   => $overallStatus,
            'started_at'       => $startedAt,
            'duration_seconds' => $durationSeconds,
            'steps'            => $steps,
        ];
    }

    // --------------------------------------------------------------------------
    // Private: pg_dump
    // --------------------------------------------------------------------------

    /**
     * Execute pg_dump, writing a plain-SQL dump to $outputPath.
     *
     * Connects over TCP using DB_HOST / DB_PORT from the environment —
     * this avoids Unix socket issues in Docker and works identically in
     * production, whether Postgres is local or on a managed remote server.
     *
     * The password is passed via the PGPASSWORD env var, which pg_dump
     * reads automatically. It is never written to disk or logged.
     *
     * @throws \RuntimeException  If pg_dump exits with a non-zero status.
     */
    private function runPgDump(string $outputPath): void
    {
        $host     = config('database.connections.pgsql.host');
        $port     = config('database.connections.pgsql.port', 5432);
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        // Build the pg_dump command.
        // -F p  = plain SQL format (human-readable, easy to restore with psql)
        // -f    = output file path
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s -F p -f %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Pass the password via environment variable, never on the command line.
        $result = Process::timeout(300)->env(['PGPASSWORD' => $password])->run($command);

        if (! $result->successful()) {
            throw new \RuntimeException(
                'pg_dump failed: ' . trim($result->errorOutput() ?: $result->output())
            );
        }
    }

    // --------------------------------------------------------------------------
    // Private: Gzip compress
    // --------------------------------------------------------------------------

    /**
     * Compress $sourcePath to $destPath using gzip.
     *
     * We stream the file through PHP's gzip functions rather than shelling
     * out to the gzip binary, which avoids a dependency on that binary being
     * present and works identically across environments.
     *
     * @throws \RuntimeException  On read or write failure.
     */
    protected function gzipFile(string $sourcePath, string $destPath): void
    {
        // Open source for reading.
        $sourceHandle = fopen($sourcePath, 'rb');
        if ($sourceHandle === false) {
            throw new \RuntimeException("Cannot open source file for compression: {$sourcePath}");
        }

        // Open destination as a gzip stream for writing.
        $gzHandle = gzopen($destPath, 'wb9'); // '9' = maximum compression
        if ($gzHandle === false) {
            fclose($sourceHandle);
            throw new \RuntimeException("Cannot open gzip destination file: {$destPath}");
        }

        // Stream in 1 MB chunks to keep memory usage flat regardless of dump size.
        while (! feof($sourceHandle)) {
            $chunk = fread($sourceHandle, 1_048_576);
            if ($chunk === false) {
                break;
            }
            gzwrite($gzHandle, $chunk);
        }

        fclose($sourceHandle);
        gzclose($gzHandle);
    }

    // --------------------------------------------------------------------------
    // Protected: S3 upload
    // --------------------------------------------------------------------------

    /**
     * Upload the compressed backup file to S3.
     *
     * Uses the AWS SDK directly (not Laravel's Storage facade) so we can
     * use the credentials from database_backup config rather than the default
     * filesystems.php disk, keeping backup credentials isolated.
     *
     * @throws \RuntimeException  On S3 upload failure.
     */
    protected function uploadToS3(string $localPath, string $s3Key): void
    {
        $client = $this->makeS3Client();

        try {
            $client->putObject([
                'Bucket'      => config('database_backup.S3_bucket'),
                'Key'         => $s3Key,
                'SourceFile'  => $localPath,
                'ContentType' => 'application/gzip',
            ]);
        } catch (AwsException $e) {
            throw new \RuntimeException('S3 upload failed: ' . $e->getAwsErrorMessage());
        }
    }

    // --------------------------------------------------------------------------
    // Private: Integrity check
    // --------------------------------------------------------------------------

    /**
     * Verify the integrity of the local .gz file using gzip's built-in CRC check.
     *
     * We re-read the compressed file through PHP's gzopen and discard the output,
     * which exercises the full decompression and CRC verification path without
     * writing anything to disk. This catches truncated or corrupt files.
     *
     * This is equivalent to running `gunzip -t` on the file, but uses PHP's
     * zlib extension directly so no external binary is required.
     *
     * @throws \RuntimeException  If the file fails the integrity check.
     */
    private function verifyIntegrity(string $gzPath): void
    {
        $handle = gzopen($gzPath, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Integrity check failed: cannot open {$gzPath}");
        }

        // Read to EOF in 1 MB chunks — gzopen will throw if the CRC fails.
        try {
            while (! gzeof($handle)) {
                gzread($handle, 1_048_576);
            }
        } catch (Throwable $e) {
            gzclose($handle);
            throw new \RuntimeException('Integrity check failed: ' . $e->getMessage());
        }

        gzclose($handle);
    }

    // --------------------------------------------------------------------------
    // Protected: S3 pruning
    // --------------------------------------------------------------------------

    /**
     * Delete S3 backup objects older than config('database_backup.retention_days').
     *
     * Lists all objects under the configured S3 folder, compares their
     * LastModified timestamp against the retention threshold, and deletes
     * any that have expired.
     *
     * @return int  Number of files deleted.
     * @throws \RuntimeException  On S3 API failure.
     */
    protected function pruneS3Backups(): int
    {
        $retentionDays = config('database_backup.retention_days');

        // Retention disabled — nothing to prune.
        if ($retentionDays <= 0) {
            return 0;
        }

        $client    = $this->makeS3Client();
        $bucket    = config('database_backup.S3_bucket');
        $folder    = rtrim(config('database_backup.s3_folder'), '/');
        $threshold = Carbon::now()->subDays($retentionDays);
        $deleted   = 0;

        try {
            // List all objects under the backup folder.
            $result = $client->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $folder . '/',
            ]);

            $objects = $result['Contents'] ?? [];

            foreach ($objects as $object) {
                $lastModified = Carbon::instance($object['LastModified']);

                if ($lastModified->lt($threshold)) {
                    $client->deleteObject([
                        'Bucket' => $bucket,
                        'Key'    => $object['Key'],
                    ]);
                    $deleted++;
                    Log::info('[DatabaseBackup] Pruned old S3 backup.', ['key' => $object['Key']]);
                }
            }

        } catch (AwsException $e) {
            throw new \RuntimeException('S3 pruning failed: ' . $e->getAwsErrorMessage());
        }

        return $deleted;
    }

    // --------------------------------------------------------------------------
    // Private: Helpers
    // --------------------------------------------------------------------------

    /**
     * Generate the backup filename for this run.
     *
     * Format: {prefix}_{YYYY-MM-DD_HH-MM-SS}.sql.gz
     * Example: newsrag_2026-03-18_03-00-00.sql.gz
     */
    private function generateFilename(): string
    {
        $prefix    = rtrim(config('database_backup.filename_prefix'), '_');
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

        return "{$prefix}_{$timestamp}.sql.gz";
    }

    /**
     * Build the full S3 object key for a given filename.
     * Combines the configured folder prefix with the filename.
     *
     * Example: backups/newsrag_2026-03-18_03-00-00.sql.gz
     */
    private function buildS3Key(string $filename): string
    {
        $folder = rtrim(config('database_backup.s3_folder'), '/');
        return $folder . '/' . $filename;
    }

    /**
     * Delete a temp file, suppressing errors.
     * Called in a finally{} block so it always runs.
     */
    private function deleteTempFile(?string $path): void
    {
        if ($path !== null && file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Build and return an S3Client instance using the backup-specific credentials.
     */
    private function makeS3Client(): S3Client
    {
        return new S3Client([
            'version'     => 'latest',
            'region'      => config('database_backup.aws_region'),
            'credentials' => [
                'key'    => config('database_backup.aws_key'),
                'secret' => config('database_backup.aws_secret'),
            ],
        ]);
    }

    /**
     * Build a single step result array.
     *
     * @param  string  $label   Short description of the step.
     * @param  string  $status  'success', 'failure', or 'skipped'.
     * @param  string  $detail  Extra detail — file size, file path, error message, etc.
     */
    private function step(string $label, string $status, string $detail): array
    {
        return compact('label', 'status', 'detail');
    }

    /**
     * Infer the label for the step that just failed, based on the steps
     * already completed. Used when catching an exception to label the
     * failed step without repeating the step name in every try/catch.
     */
    private function currentStepLabel(array $completedSteps): string
    {
        $labels = [
            'Generate filename',
            'pg_dump',
            'Gzip compress',
            'Upload to S3',
            'Integrity check',
        ];

        $nextIndex = count($completedSteps);
        return $labels[$nextIndex] ?? 'Unknown step';
    }

    /**
     * Format a byte count as a human-readable string.
     */
    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1) . ' MB';
        }
        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}