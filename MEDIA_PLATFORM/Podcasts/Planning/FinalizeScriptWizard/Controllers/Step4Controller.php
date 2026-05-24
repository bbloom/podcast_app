<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step4Controller extends Controller
{
    /**
     * Render the AI proofing step.
     * Displays two editable textareas: the canonical script and an ephemeral
     * scratch pad for pasting AI-modified versions for comparison.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        return view('media_platform.podcasts.planning.finalize_script_wizard.step4', compact('episode'));
    }

    /**
     * Save the scratch pad content to script_scratch.
     * Called by Alpine.js fetch() — returns JSON. The canonical script field
     * is saved separately via the existing podcast_episodes_planning.script.save
     * route (EditScriptFieldController), which is also called from this page.
     */
    public function saveScratch(Request $request): JsonResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $episode->update(['script_scratch' => $request->input('script_scratch')]);

        return response()->json(['success' => true, 'message' => 'Scratch saved.']);
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