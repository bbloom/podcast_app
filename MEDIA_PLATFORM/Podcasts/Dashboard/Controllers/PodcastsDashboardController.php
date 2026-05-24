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
     * Passes four variables to the view:
     *
     *   $planningByShow      — planning episodes grouped by show, each group
     *                          sorted by status pipeline order (sortOrder()).
     *                          Shows are ordered per ACTIVE_SHOWS.
     *
     *   $episodesInProduction — published records that need attention:
     *                          excludes `published` and `not_published`.
     *
     *   $recentlyPublished   — the 5 most recently published episodes.
     *
     *   $hasPendingScratch   — true if any planning episode for this user has
     *                          a non-null script_scratch (AI proofing in progress
     *                          or not yet cleared). Triggers an advisory notice.
     */
    public function show()
    {
        $userId        = auth()->id();
        $orderedTitles = self::ACTIVE_SHOWS;

        // ── Planning episodes ────────────────────────────────────────────────
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

        // ── AI scratch pad advisory ──────────────────────────────────────────
        // True if any planning episode has an unsaved/uncleared script_scratch.
        // Cleared automatically by Step 9 of the Finalize Script Wizard.
        $hasPendingScratch = PodcastEpisodePlanning::forUser($userId)
            ->whereNotNull('script_scratch')
            ->where('script_scratch', '!=', '')
            ->exists();

        return view('media_platform.podcasts.dashboard.dashboard', compact(
            'planningByShow',
            'episodesInProduction',
            'recentlyPublished',
            'hasPendingScratch',
        ));
    }
}