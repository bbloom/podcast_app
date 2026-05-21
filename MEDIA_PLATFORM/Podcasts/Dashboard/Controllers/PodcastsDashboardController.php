<?php

// =============================================================================
// PodcastsDashboardController
//
// The main entry point for the Podcast Studio. Surfaces the assembly line:
// planning episodes grouped by show then sorted by pipeline progression,
// episodes in post-production needing attention, and recently published.
//
// Path: MEDIA_PLATFORM/Podcasts/Dashboard/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

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
     * Passes three variables to the view:
     *
     *   $planningByShow      — planning episodes grouped by show, each group
     *                          sorted by status pipeline order (sortOrder()).
     *                          Shows are ordered per ACTIVE_SHOWS.
     *
     *   $episodesInProduction — published records that need attention:
     *                          excludes `published` and `not_published`.
     *
     *   $recentlyPublished   — the 5 most recently published episodes.
     */
    public function show()
    {
        $userId        = auth()->id();
        $orderedTitles = self::ACTIVE_SHOWS;

        // ── Planning episodes — grouped by show, sorted by status order ──────
        //
        // 1. Fetch all planning records with their show.
        // 2. Sort: first by the show's position in ACTIVE_SHOWS, then within
        //    each show by the status's pipeline sortOrder().
        // 3. Group by podcast_show_id so the view can render a show header row
        //    for each show that has at least one planning episode.

        $planningByShow = PodcastEpisodePlanning::forUser($userId)
            ->with('show')
            ->get()
            ->sortBy([
                fn ($a, $b) => array_search($a->show->title, $orderedTitles)
                           <=> array_search($b->show->title, $orderedTitles),
                fn ($a, $b) => $a->status->sortOrder()
                           <=> $b->status->sortOrder(),
            ])
            ->groupBy('podcast_show_id');

        // ── Post-production episodes needing attention ───────────────────────
        //
        // Excludes `published` and `not_published` — those are terminal states
        // that don't need user action. Everything else is in-flight.

        $episodesInProduction = PodcastEpisode::forUser($userId)
            ->whereNotIn('status', [
                PodcastEpisodeStatus::published->value,
                PodcastEpisodeStatus::not_published->value,
            ])
            ->with('show')
            ->orderByScheduledDate()
            ->orderBy('title', 'asc')
            ->get();

        // ── Recently published ───────────────────────────────────────────────

        $recentlyPublished = PodcastEpisode::forUser($userId)
            ->where('status', PodcastEpisodeStatus::published->value)
            ->with('show')
            ->orderByDesc('scheduled_date')
            ->limit(5)
            ->get();

        return view('media_platform.podcasts.dashboard.dashboard', compact(
            'planningByShow',
            'episodesInProduction',
            'recentlyPublished',
        ));
    }
}