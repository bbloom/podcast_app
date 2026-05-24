<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step5Controller extends Controller
{
    /**
     * Render the intro template review step.
     *
     * If the show has an intro template, the user can review and optionally
     * save permanent changes to the template, or continue without saving.
     *
     * If the show has no intro template, the user is prompted to create one
     * inline — the wizard does not proceed until a template is saved.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        $episode->load('show');

        $hasIntro = filled($episode->show?->intro_template);

        return view('media_platform.podcasts.planning.finalize_script_wizard.step5', [
            'episode'       => $episode,
            'hasIntro'      => $hasIntro,
            'introTemplate' => $episode->show->intro_template ?? '',
        ]);
    }

    /**
     * Handle the intro template review.
     *
     * _action = 'save'     — validate and permanently update the show's intro_template,
     *                        then redirect to step 6.
     * _action = 'continue' — no changes, redirect to step 6.
     */
    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired.');
        }

        if ($request->input('_action') === 'save') {
            $request->validate([
                'intro_template' => ['required', 'string'],
            ], [
                'intro_template.required' => 'The intro template cannot be empty.',
            ]);

            $episode->load('show');
            $episode->show->update(['intro_template' => $request->input('intro_template')]);
        }

        return redirect()->route('podcast_episodes_planning.wizard.finalize.step6');
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