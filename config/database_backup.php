<?php

// ============================================================================
// config/database_backup.php
//
// Configuration for the DatabaseBackup package.
//
// All sensitive values (bucket name, credentials) should live in .env.
// This file maps those env vars to named config keys with sensible defaults.
//
// Namespace: DatabaseBackup\
// Artisan command: backup:database
// ============================================================================

return [

    // --------------------------------------------------------------------------
    // Filename prefix
    //
    // A short string prepended to every backup filename, to make it easy to
    // identify which application produced the file when browsing S3.
    //
    // The full filename will be: {prefix}_{YYYY-MM-DD_HH-MM-SS}.sql.gz
    // Example: newsrag_2026-03-18_03-00-00.sql.gz
    // --------------------------------------------------------------------------
    'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'newsrag'),

    // --------------------------------------------------------------------------
    // S3 bucket
    //
    // The name of the AWS S3 bucket where backup files are uploaded.
    // Must already exist — this package does not create the bucket.
    // --------------------------------------------------------------------------
    'S3_bucket' => env('BACKUP_S3_BUCKET', ''),

    // --------------------------------------------------------------------------
    // S3 folder (object key prefix)
    //
    // The folder path inside the S3 bucket where backup files are stored.
    // Do not include a leading or trailing slash.
    // Example: 'backups' → s3://my-bucket/backups/newsrag_2026-03-18.sql.gz
    // --------------------------------------------------------------------------
    's3_folder' => env('BACKUP_S3_FOLDER', 'backups'),

    // --------------------------------------------------------------------------
    // AWS credentials
    //
    // The IAM key pair used to authenticate with S3.
    // The IAM user/role should have s3:PutObject, s3:GetObject,
    // s3:ListBucket, and s3:DeleteObject on the target bucket only.
    // --------------------------------------------------------------------------
    'aws_key'    => env('BACKUP_AWS_KEY', ''),
    'aws_secret' => env('BACKUP_AWS_SECRET', ''),
    'aws_region' => env('BACKUP_AWS_REGION', 'us-east-1'),

    // --------------------------------------------------------------------------
    // S3 backup retention (days)
    //
    // Backup files in S3 that are older than this many days are automatically
    // deleted at the end of each successful backup run.
    //
    // Set to 0 to disable pruning (not recommended — S3 costs accumulate).
    // --------------------------------------------------------------------------
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    // --------------------------------------------------------------------------
    // Log table retention (days)
    //
    // Rows in the database_backup_logs table older than this many days are
    // pruned at the end of each backup run, keeping the log table lean.
    //
    // Set to 0 to keep all log rows indefinitely.
    // --------------------------------------------------------------------------
    'log_retention_days' => (int) env('BACKUP_LOG_RETENTION_DAYS', 90),

    // --------------------------------------------------------------------------
    // Temporary local path
    //
    // The directory where the .sql and .sql.gz files are written during the
    // backup process, before upload to S3. The temp file is always deleted
    // after the upload completes (or fails), via a finally{} block.
    //
    // Must be an absolute path or a path resolvable by storage_path().
    // Defaults to storage/app/temp/ — ensure this directory exists and is
    // writable by the web/CLI process.
    // --------------------------------------------------------------------------
    'temp_path' => env('BACKUP_TEMP_PATH', storage_path('app/temp')),

    // --------------------------------------------------------------------------
    // Failure notification email
    //
    // The email address that receives a notification when a backup fails.
    // Typically the application administrator.
    //
    // Set to null or empty string to disable failure emails.
    // --------------------------------------------------------------------------
    'notification_email' => env('BACKUP_NOTIFICATION_EMAIL', ''),

];