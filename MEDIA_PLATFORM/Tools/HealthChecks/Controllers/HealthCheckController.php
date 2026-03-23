<?php

namespace MediaPlatform\Tools\HealthChecks\Controllers;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Routing\Controller;

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
}
