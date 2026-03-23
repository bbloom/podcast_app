<?php

// ============================================================================
// MEDIA_PLATFORM/DatabaseBackup/routes/database_backup_routes.php
//
// Add `require __DIR__.'/database_backup_routes.php';` to routes/web.php.
//
// Both routes are protected by auth + can:admin middleware.
// The prefix 'admin' keeps them consistent with health-checks and other
// admin-only areas of the application.
// ============================================================================

use MediaPlatform\Tools\DatabaseBackup\Controllers\DatabaseBackupController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

// Display the backup log table and "Run Now" button.
Route::get('/admin/database-backups', [DatabaseBackupController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('admin.database-backups.index');

// Trigger a backup run synchronously and redirect back with the result.
Route::post('/admin/database-backups/run', [DatabaseBackupController::class, 'runNow'])
    ->middleware(['auth', 'can:admin'])
    ->name('admin.database-backups.run');
