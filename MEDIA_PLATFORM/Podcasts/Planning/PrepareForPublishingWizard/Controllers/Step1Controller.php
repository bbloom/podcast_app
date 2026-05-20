<?php

namespace MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Step1Controller extends Controller
{
    /**
     * Render the wizard introduction and checklist page.
     * Guards: episode must belong to the user and have status ready_for_publishing.
     * Stores the episode ID in session for subsequent steps.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcast_episode_planning->status !== PodcastEpisodePlanningStatus::ready_for_publishing) {
            return redirect()
                ->route('podcast_episodes_planning.show', $podcast_episode_planning)
                ->with('error', 'This episode is not ready for publishing. Set the status to "Ready For Publishing" first.');
        }

        $podcast_episode_planning->load(['show', 'guests', 'links']);

        session(['wizard.prepare_for_publishing.episode_id' => $podcast_episode_planning->id]);

        return view('media_platform.podcasts.planning.prepare_for_publishing_wizard.step1', [
            'episode' => $podcast_episode_planning,
        ]);
    }
}