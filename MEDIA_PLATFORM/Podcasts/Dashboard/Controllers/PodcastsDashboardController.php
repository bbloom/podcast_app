<?php

// =============================================================================
// PodcastsDashboardController
//
// The main entry point for the Podcast Studio. Surfaces the assembly line:
// planning episodes in progress, episodes in post-production, and recently
// published episodes — per show and in aggregate.
//
// Path: MEDIA_PLATFORM/Podcasts/Dashboard/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;

class PodcastsDashboardController extends Controller
{
    // The five active shows, in display order.
    private const ACTIVE_SHOWS = [
        'The Bob Bloom Show',
        'The Bob Bloom Interviews',
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'PHP Serverless Project Updates',
    ];

    /**
     * Display the Podcasts dashboard.
     *
     * Passes three collections to the view:
     *   - $planningEpisodes    — all planning records for the user, with show eager-loaded
     *   - $episodesInProduction — published records not yet at 'published' status
     *   - $recentlyPublished   — the 5 most recently published episodes
     *   - $shows               — the five active shows with planning + production counts
     */
    public function show()
    {
        $userId        = auth()->id();
        $orderedTitles = self::ACTIVE_SHOWS;

        // ── Planning episodes ────────────────────────────────────────────────
        // All planning records for the user, regardless of status.
        // Ordered by scheduled date ascending (nulls last), then title.
        $planningEpisodes = PodcastEpisodePlanning::forUser($userId)
            ->with('show')
            ->orderByRaw('scheduled_date IS NULL')
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('title', 'asc')
            ->get();

        // ── Episodes in post-production ──────────────────────────────────────
        // Published records that have not yet reached 'published' status.
        $episodesInProduction = PodcastEpisode::forUser($userId)
            ->where('status', '!=', 'published')
            ->with('show')
            ->orderByScheduledDate()
            ->orderBy('title', 'asc')
            ->get();

        // ── Recently published ───────────────────────────────────────────────
        $recentlyPublished = PodcastEpisode::forUser($userId)
            ->where('status', 'published')
            ->with('show')
            ->orderByDesc('scheduled_date')
            ->limit(5)
            ->get();

        // ── Shows overview ───────────────────────────────────────────────────
        // Counts planning and post-production episodes per show.
        $shows = PodcastShow::where('user_id', $userId)
            ->whereIn('title', $orderedTitles)
            ->withCount([
                'planningEpisodes as planning_count',
                'episodes as in_production_count' => function ($q) {
                    $q->where('status', '!=', 'published');
                },
            ])
            ->get()
            ->sortBy(fn ($show) => array_search($show->title, $orderedTitles))
            ->values();

        return view('media_platform.podcasts.dashboard.dashboard', compact(
            'planningEpisodes',
            'episodesInProduction',
            'recentlyPublished',
            'shows',
        ));
    }
}