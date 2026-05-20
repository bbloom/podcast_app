<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step6Controller extends Controller
{
    /**
     * Render the outro-append step.
     * Auto-skips to Step 7 if no outro_template is set on the show.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        $episode->load('show');

        if (! $episode->show?->outro_template) {
            return redirect()
                ->route('podcast_episodes_planning.wizard.finalize.step7')
                ->with('info', 'This show has no outro template. Skipping to final confirmation.');
        }

        $resolvedOutro = $this->resolveTemplate($episode->show->outro_template, $episode);

        return view('media_platform.podcasts.planning.finalize_script_wizard.step6', [
            'episode'       => $episode,
            'resolvedOutro' => $resolvedOutro,
        ]);
    }

    /**
     * Append the outro text to the script, or skip without modifying it.
     * Accepts _action = 'append' | 'skip'.
     */
    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        if ($request->input('_action') === 'append') {
            $outroText = $request->input('outro_text', '');
            $episode->update(['script' => ($episode->script ?? '') . "\n\n" . $outroText]);
        }

        return redirect()->route('podcast_episodes_planning.wizard.finalize.step7');
    }

    private function resolveTemplate(string $template, PodcastEpisodePlanning $episode): string
    {
        $sponsorNames = PhpServerlessProjectSponsor::where('enabled', true)
            ->where('former_sponsor', false)
            ->orderBy('full_name')
            ->pluck('full_name')
            ->implode("\n");

        return str_replace(
            ['{{episode_number}}', '{{title}}', '{{sponsors}}'],
            [$episode->episode_number ?? '', $episode->title, $sponsorNames],
            $template
        );
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