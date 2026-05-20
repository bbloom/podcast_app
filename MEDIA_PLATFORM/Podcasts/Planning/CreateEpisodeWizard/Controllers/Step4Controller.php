<?php

namespace MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Step4Controller extends Controller
{
    /**
     * Render the confirmation / "what next?" screen for the newly created episode.
     * The episode is passed as a route model binding.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        $podcast_episode_planning->load('show');

        return view('media_platform.podcasts.planning.create_episode_wizard.step4', [
            'episode' => $podcast_episode_planning,
        ]);
    }
}