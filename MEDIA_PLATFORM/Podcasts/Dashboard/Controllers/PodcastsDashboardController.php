<?php

// =============================================================================
// PodcastStudioDashboardController
//
// The main entry point for the Podcast Studio. Surfaces the assembly line:
// drafts in progress, drafts ready for production, episodes in production,
// and quick actions to get into the work.
//
// Path: MEDIA_PLATFORM/PodcastStudio/Dashboard/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;

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
     * Display the Podcast Studio dashboard.
     */
    public function show()
    {
        $userId = auth()->id();
        $orderedTitles = self::ACTIVE_SHOWS;

        // ── Drafts in progress ──────────────────────────────────────────
        $draftsInProgress = PodcastEpisodeDraft::forUser($userId)
            ->where('status', PodcastEpisodeDraftStatus::working_on_draft)
            ->with('show')
            ->orderBy('date')
            ->orderBy('title')
            ->get();

        // ── Drafts ready for production ─────────────────────────────────
        $draftsReadyForProduction = PodcastEpisodeDraft::forUser($userId)
            ->where('status', PodcastEpisodeDraftStatus::ready_to_create_production_episode)
            ->with('show')
            ->orderBy('date')
            ->orderBy('title')
            ->get();

        // ── Episodes in production (not yet published) ──────────────────
        $episodesInProduction = PodcastEpisode::where('user_id', $userId)
            ->where('status', '!=', 'published')
            ->with('show')
            ->orderBy('scheduled_date')
            ->orderBy('title')
            ->get();

        // ── Recently published ──────────────────────────────────────────
        $recentlyPublished = PodcastEpisode::where('user_id', $userId)
            ->where('status', 'published')
            ->with('show')
            ->orderByDesc('scheduled_date')
            ->limit(5)
            ->get();

        // ── Shows (for the overview, in display order) ──────────────────
        $shows = PodcastShow::where('user_id', $userId)
            ->whereIn('title', $orderedTitles)
            ->withCount([
                'drafts as drafts_in_progress_count' => function ($q) {
                    $q->where('status', PodcastEpisodeDraftStatus::working_on_draft);
                },
                'drafts as drafts_ready_count' => function ($q) {
                    $q->where('status', PodcastEpisodeDraftStatus::ready_to_create_production_episode);
                },
                'episodes as episodes_in_production_count' => function ($q) {
                    $q->where('status', '!=', 'published');
                },
            ])
            ->get()
            ->sortBy(fn ($show) => array_search($show->title, $orderedTitles))
            ->values();

        return view('media_platform.podcasts.dashboard.dashboard', compact(
            'draftsInProgress',
            'draftsReadyForProduction',
            'episodesInProduction',
            'recentlyPublished',
            'shows',
        ));
    }
}