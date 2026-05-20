<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step2Controller extends Controller
{
    /**
     * Render the episode-number confirmation step.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        return view('media_platform.podcasts.planning.finalize_script_wizard.step2', compact('episode'));
    }

    /**
     * Save the (possibly updated) episode number and advance to Step 3.
     */
    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $validated = $request->validate([
            'episode_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $episode->update(['episode_number' => $validated['episode_number']]);

        return redirect()->route('podcast_episodes_planning.wizard.finalize.step3');
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