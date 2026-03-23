<?php

// ============================================================================
// MEDIA_PLATFORM/Tools/DatabaseBackup/Mails/BackupFailedMail.php
//
// Sent to the address configured in config('database_backup.notification_email')
// whenever a backup run fails.
//
// Contains the timestamp, the filename that was being processed, and the
// error message from the step that failed — giving the admin enough
// information to diagnose the problem and run the backup manually.
// ============================================================================

namespace MediaPlatform\Tools\DatabaseBackup\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BackupFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Carbon  $failedAt      When the backup run started.
     * @param  string  $filename      The filename that was being attempted.
     * @param  string  $errorMessage  The error message from the step that failed.
     */
    public function __construct(
        public Carbon $failedAt,
        public string $filename,
        public string $errorMessage,
    ) {}

    /**
     * The email subject line.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Database backup failed — ' . $this->failedAt->format('Y-m-d H:i'),
        );
    }

    /**
     * Render the failure email.
     *
     * VIEW: views/database_backup/emails/backup_failed.blade.php
     */
    public function content(): Content
    {
        return new Content(
            view: 'database_backup.emails.backup_failed',
            with: [
                'failedAt'     => $this->failedAt,
                'filename'     => $this->filename,
                'errorMessage' => $this->errorMessage,
                'adminUrl'     => url('/admin/database-backups'),
            ],
        );
    }
}