<?php

namespace MediaPlatform\Tools\HealthChecks\Controllers;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    /**
     * Display all alerts, unresolved first (Tier 3 at top).
     */
    public function index()
    {
        $unresolvedAlerts = AdminAlert::where('is_resolved', false)
            ->orderBy('tier', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $resolvedAlerts = AdminAlert::where('is_resolved', true)
            ->orderBy('resolved_at', 'desc')
            ->limit(50)
            ->get();

        return view('media_platform.tools.health_checks.index', [
            'unresolvedAlerts' => $unresolvedAlerts,
            'resolvedAlerts'   => $resolvedAlerts,
        ]);
    }

    /**
     * Mark a Tier 3 alert as resolved.
     */
    public function resolve(AdminAlert $alert)
    {
        $alert->markResolved();

        return redirect()
            ->route('admin.health-checks.index')
            ->with('success', "Alert '{$alert->title}' marked as resolved.");
    }

    /**
     * Display the health checks reference/readme page.
     */
    public function readme()
    {
        return view('media_platform.tools.health_checks.readme');
    }

    /**
     * Show the flush failed jobs confirmation page.
     * Only reachable when there are actually failed jobs — the button
     * in the UI is conditionally rendered — but we double-check here.
     */
    public function flushFailedJobsConfirm()
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            return redirect()
                ->route('admin.health-checks.index')
                ->with('success', 'No failed jobs to flush.');
        }

        return view('media_platform.tools.health_checks.flush_failed_jobs_confirm', [
            'count' => $count,
        ]);
    }

    /**
     * Flush all failed jobs and immediately resolve the related alert.
     *
     * Runs queue:flush to clear the failed_jobs table, then resolves any
     * unresolved "Failed jobs detected" alert so the UI reflects the fix
     * immediately without waiting for the next scheduled health check.
     */
    public function flushFailedJobs()
    {
        Artisan::call('queue:flush');

        // Immediately resolve the alert rather than waiting for the next
        // scheduled health check run to auto-resolve it.
        AdminAlert::where('category', 'queue')
            ->where('title', 'Failed jobs detected')
            ->where('is_resolved', false)
            ->each(fn ($alert) => $alert->markResolved());

        return redirect()
            ->route('admin.health-checks.index')
            ->with('success', 'Failed jobs flushed and alert resolved.');
    }
}
