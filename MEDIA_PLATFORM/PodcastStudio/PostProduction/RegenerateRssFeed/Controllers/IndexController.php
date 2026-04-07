<?php

// =============================================================================
// IndexController
//
// Lists all podcast shows belonging to the authenticated user so they can
// select one to regenerate the RSS feed for.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/RegenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\RegenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class IndexController extends Controller
{
    /**
     * Display a list of all podcast shows belonging to the authenticated user.
     *
     * Any show can be regenerated at any time — there is no status gate.
     * Shows are ordered alphabetically by title.
     */
    public function __invoke(): \Illuminate\View\View
    {
        $shows = PodcastShow::where('user_id', auth()->id())
            ->orderBy('title')
            ->get();

        return view('media_platform.podcast_studio.post_production.regenerate_rss_feed.index', [
            'shows' => $shows,
        ]);
    }
}