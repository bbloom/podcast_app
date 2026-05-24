<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Step9Controller extends Controller
{
    /**
     * Render the final confirmation step.
     * Displays the complete assembled script (read-only) for a final glance.
     * No editing — all script work is done. This step solely confirms the
     * status transition to ready_to_record.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        return view('media_platform.podcasts.planning.finalize_script_wizard.step9', compact('episode'));
    }

    /**
     * Lock the script — set status to ready_to_record and clear the session.
     * Also clears script_scratch, as the AI proofing session is now complete.
     */
    public function store(): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $episode->update([
            'status'         => PodcastEpisodePlanningStatus::ready_to_record,
            'script_scratch' => null,
        ]);

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