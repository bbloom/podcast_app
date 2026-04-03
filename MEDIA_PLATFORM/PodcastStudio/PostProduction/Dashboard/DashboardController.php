<?php

namespace MediaPlatform\PodcastStudio\PostProduction\Dashboard;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display the post-production dashboard.
     */
    public function show()
    {
        return view('media_platform.podcast_studio.post_production.dashboard');
    }
}