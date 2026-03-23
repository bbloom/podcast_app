<?php

// ============================================================================
// MEDIA_PLATFORM/Tools/DatabaseBackup/Controllers/DatabaseBackupController.php
//
// Admin controller for the database backup feature.
//
// Routes:
//   GET  /admin/database-backups        → index()   — log table + Run Now button
//   POST /admin/database-backups/run    → runNow()  — trigger backup synchronously
//
// Both routes are protected by auth + can:admin middleware (see routes file).
//
// The runNow() method calls BackupService::run() synchronously. This means
// the HTTP request waits for the full backup to complete before redirecting.
// Typical duration: 5–30 seconds for a small-to-medium database.
//
// The result array is flashed to the session so the index view can render
// the step-by-step outcome after the redirect.
// ============================================================================

namespace MediaPlatform\Tools\DatabaseBackup\Controllers;

use MediaPlatform\Tools\DatabaseBackup\Models\DatabaseBackupLog;
use MediaPlatform\Tools\DatabaseBackup\Services\BackupService;
use Illuminate\Routing\Controller;

class DatabaseBackupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    // --------------------------------------------------------------------------
    // index
    // --------------------------------------------------------------------------

    /**
     * Display the backup log table and the Run Now button.
     *
     * Logs are shown newest-first, paginated at 50 per page.
     * If the session contains a 'backup_result' (set by runNow after a
     * manual run), the view renders the step-by-step outcome at the top.
     */
    public function index()
    {
        $logs = DatabaseBackupLog::orderBy('ran_at', 'desc')
            ->paginate(50);

        return view('media_platform.tools.database_backup.index', [
            'logs'          => $logs,
            'backupResult'  => session('backup_result'),
        ]);
    }

    // --------------------------------------------------------------------------
    // runNow
    // --------------------------------------------------------------------------

    /**
     * Trigger a full backup run synchronously and redirect back to the index.
     *
     * The step-by-step result is flashed to the session so the index view
     * can render it as immediate feedback to the admin.
     *
     * Note on synchronous execution: the admin explicitly chose to run this.
     * A 10–30 second wait is acceptable and gives clear, immediate feedback.
     * The backup sequence writes a log row to the database regardless of
     * outcome, so the result is always persisted even if the browser
     * disconnects mid-request.
     */
    public function runNow(BackupService $service)
    {
        $result = $service->run();

        return redirect()
            ->route('admin.database-backups.index')
            ->with('backup_result', $result);
    }
}