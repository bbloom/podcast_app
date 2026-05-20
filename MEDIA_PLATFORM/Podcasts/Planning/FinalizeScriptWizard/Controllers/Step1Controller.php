<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Step1Controller extends Controller
{
    /**
     * Render the wizard introduction page.
     * Guards: episode must belong to the user and have status ready_to_finalize_the_script.
     * Stores the episode ID in session for all subsequent wizard steps.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        if ($podcast_episode_planning->status !== PodcastEpisodePlanningStatus::ready_to_finalize_the_script) {
            return redirect()
                ->route('podcast_episodes_planning.show', $podcast_episode_planning)
                ->with('error', 'This episode is not ready to finalize the script. Set the status to "Ready To Finalize The Script" first.');
        }

        session(['wizard.finalize_script.episode_id' => $podcast_episode_planning->id]);

        $podcast_episode_planning->load('show');

        return view('media_platform.podcasts.planning.finalize_script_wizard.step1', [
            'episode' => $podcast_episode_planning,
        ]);
    }
}