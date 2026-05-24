<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step3Controller extends Controller
{
    /**
     * Render the title confirmation step.
     * Displays the title in derived format: #N — Title.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        return view('media_platform.podcasts.planning.finalize_script_wizard.step3', compact('episode'));
    }

   

    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                // (?!\d|#\d) is a negative lookahead — rejects any title 
                // that starts with a digit, or starts with # immediately 
                // followed by a digit. A title like #ThrowbackThursday would still 
                // pass since #T doesn't match #\d
                'regex:/^(?!\d|#\d)/u',
            ],
        ], [
           'title.regex' => 'Do not include the episode number in the title. Enter the title only — for example "My Great Episode", not "#12 — My Great Episode". The episode number prefix is added automatically when the episode is published.',
        ]);

        $episode->update(['title' => $validated['title']]);

        return redirect()->route('podcast_episodes_planning.wizard.finalize.step4');
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