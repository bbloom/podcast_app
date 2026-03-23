<?php

// ============================================================================
// MEDIA_PLATFORM/DatabaseBackup/Models/DatabaseBackupLog.php
//
// Eloquent model for the database_backup_logs table.
//
// These rows are immutable — they are only ever inserted, never updated.
// The model deliberately omits updated_at to reflect this.
// ============================================================================

namespace MediaPlatform\Tools\DatabaseBackup\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseBackupLog extends Model
{
    // --------------------------------------------------------------------------
    // Table
    // --------------------------------------------------------------------------

    protected $table = 'database_backup_logs';

    // --------------------------------------------------------------------------
    // Mass-assignable columns
    // --------------------------------------------------------------------------

    protected $fillable = [
        'status',           // 'success' or 'failure'
        'filename',         // S3 object key, e.g. backups/newsrag_2026-03-18_03-00-00.sql.gz
        'file_size_bytes',  // size of the compressed file
        'duration_seconds', // wall-clock time for the full backup run
        'message',          // human-readable summary or error detail
        'ran_at',           // when the run started
        'created_at',       // when the row was inserted — fillable so tests can backdate rows
    ];

    // --------------------------------------------------------------------------
    // Timestamps
    //
    // We keep created_at (records when the row was written) but disable
    // updated_at because these rows are never modified after insertion.
    // --------------------------------------------------------------------------

    const UPDATED_AT = null;

    // --------------------------------------------------------------------------
    // Casts
    // --------------------------------------------------------------------------

    protected $casts = [
        'ran_at'     => 'datetime',
        'created_at' => 'datetime',
    ];

    // --------------------------------------------------------------------------
    // Convenience accessors
    // --------------------------------------------------------------------------

    /**
     * Returns true if this log entry represents a successful backup run.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Returns true if this log entry represents a failed backup run.
     */
    public function isFailure(): bool
    {
        return $this->status === 'failure';
    }

    /**
     * Returns the file size as a human-readable string, e.g. "4.2 MB".
     * Returns null if file_size_bytes is not set (failure case).
     */
    public function humanFileSize(): ?string
    {
        if ($this->file_size_bytes === null) {
            return null;
        }

        $bytes = $this->file_size_bytes;

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1) . ' MB';
        }

        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}