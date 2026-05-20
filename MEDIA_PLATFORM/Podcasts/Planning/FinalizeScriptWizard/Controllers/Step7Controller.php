<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Step7Controller extends Controller
{
    /**
     * Render the final proof step.
     * Displays the complete assembled script for final review before locking.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        return view('media_platform.podcasts.planning.finalize_script_wizard.step7', compact('episode'));
    }

    /**
     * Lock the script — set status to ready_to_record and clear the session.
     * This is the point of no return for the Finalize Script Wizard.
     */
    public function store(): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $episode->update(['status' => PodcastEpisodePlanningStatus::ready_to_record]);

        session()->forget('wizard.finalize_script.episode_id');

        return redirect()
            ->route('podcast_episodes_planning.show', $episode)
            ->with('success', 'Script finalized. Episode is ready to record!');
    }

    private function getEpisodeFromSession(): ?PodcastEpisodePlanning
    {
        $id = session('wizard.finalize_script.episode_id');
        if (! $id) return null;
        $episode = PodcastEpisodePlanning::find($id);
        if (! $episode || $episode->user_id !== auth()->id()) return null;
        return $episode;
    }
}