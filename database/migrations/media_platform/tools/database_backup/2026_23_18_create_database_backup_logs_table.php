<?php

// ============================================================================
// Migration: create_database_backup_logs_table
//
// Records every backup run — both successes and failures — so the admin
// can see the full history at a glance in the admin UI.
//
// Rows are immutable once written. There is no updated_at column.
// Old rows are pruned automatically by the BackupService according to
// config('database_backup.log_retention_days').
//
// IMPORTANT: Register this migration path in AppServiceProvider:
//   $this->loadMigrationsFrom(database_path('migrations/database_backup'));
// ============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backup_logs', function (Blueprint $table) {

            $table->comment('Records every database backup run. Immutable — rows are never updated, only inserted and eventually pruned.');

            $table->id();

            // ------------------------------------------------------------------
            // Whether this run succeeded or failed.
            // 'success' = dump + upload + integrity check all passed.
            // 'failure' = any step failed; see the message column for detail.
            // ------------------------------------------------------------------
            $table->enum('status', ['success', 'failure'])
                  ->comment('Outcome of the backup run: success or failure.');

            // ------------------------------------------------------------------
            // The full S3 object key that was (or was attempted to be) uploaded.
            // Includes the folder prefix and filename, e.g.:
            //   backups/newsrag_2026-03-18_03-00-00.sql.gz
            // Null if the run failed before a filename was generated.
            // ------------------------------------------------------------------
            $table->string('filename')
                  ->nullable()
                  ->comment('The S3 object key of the uploaded backup file. Null if the run failed before upload.');

            // ------------------------------------------------------------------
            // Size of the compressed .sql.gz file in bytes.
            // Useful for spotting anomalously small (possibly corrupt) dumps.
            // Null on failure.
            // ------------------------------------------------------------------
            $table->unsignedBigInteger('file_size_bytes')
                  ->nullable()
                  ->comment('Size of the compressed backup file in bytes. Null on failure.');

            // ------------------------------------------------------------------
            // Wall-clock duration of the entire backup sequence, in seconds.
            // Helps spot performance regressions over time.
            // ------------------------------------------------------------------
            $table->unsignedSmallInteger('duration_seconds')
                  ->nullable()
                  ->comment('Total wall-clock time for the backup sequence, in seconds.');

            // ------------------------------------------------------------------
            // Human-readable outcome message. On success, a short summary
            // (e.g. "Uploaded 4.2 MB to s3://..."). On failure, the exception
            // message or the step that failed plus the error detail.
            // ------------------------------------------------------------------
            $table->text('message')
                  ->nullable()
                  ->comment('Human-readable summary. On failure, contains the error message and the step that failed.');

            // ------------------------------------------------------------------
            // The exact timestamp when the backup run was started.
            // Distinct from created_at (which is when the row was inserted,
            // i.e. when the run finished).
            // ------------------------------------------------------------------
            $table->timestamp('ran_at')
                  ->comment('Timestamp when the backup run was started (not when the row was written).');

            // created_at records when the row was inserted (run completed).
            // No updated_at — these rows are never modified after insertion.
            $table->timestamp('created_at')
                  ->comment('Timestamp when this log row was inserted (i.e. when the run finished).');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backup_logs');
    }
};