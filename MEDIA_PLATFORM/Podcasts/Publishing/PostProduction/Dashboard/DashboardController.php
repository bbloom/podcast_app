<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\Dashboard;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display the post-production dashboard.
     */
    public function show()
    {
        return view('media_platform.podcasts.publishing.post_production.dashboard');
    }
}