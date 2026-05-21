<?php

// =============================================================================
// RecordingViewController
//
// Displays the assembled script, guest profiles, and episode links for
// a planning episode that is ready to record.
//
// Entry point: podcast_episodes_planning.recording.show
// Status gate: ready_to_record only
//
// This is a read-only view — nothing is modified here.
//
// Path: MEDIA_PLATFORM/Podcasts/Planning/RecordingView/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Planning\RecordingView\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecordingViewController extends Controller
{
    /**
     * Display the recording view for a planning episode.
     *
     * Ownership check: only the owning user may view their episode.
     * Status gate: only episodes at ready_to_record may be viewed here.
     *
     * Eager-loads the show, guests, and links so the view has everything
     * it needs in a single query.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        // ── Ownership check ──────────────────────────────────────────────────
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status gate ──────────────────────────────────────────────────────
        if ($podcast_episode_planning->status !== PodcastEpisodePlanningStatus::ready_to_record) {
            return redirect()
                ->route('podcast_episodes_planning.show', $podcast_episode_planning)
                ->with('error', 'This episode is not ready to record yet.');
        }

        // ── Eager load ───────────────────────────────────────────────────────
        $podcast_episode_planning->load(['show', 'guests', 'links']);

        return view('media_platform.podcasts.planning.recording_view.show', [
            'episode' => $podcast_episode_planning,
        ]);
    }
}