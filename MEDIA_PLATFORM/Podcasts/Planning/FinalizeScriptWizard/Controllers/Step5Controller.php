<?php

namespace MediaPlatform\Podcasts\Planning\FinalizeScriptWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step5Controller extends Controller
{
    /**
     * Render the intro-prepend step.
     * Resolves the show's intro_template with live placeholder values.
     * Auto-skips to Step 6 if no intro_template is set on the show.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        $episode->load('show');

        // Auto-skip if the show has no intro template.
        if (! $episode->show?->intro_template) {
            return redirect()
                ->route('podcast_episodes_planning.wizard.finalize.step6')
                ->with('info', 'This show has no intro template. Skipping to the outro step.');
        }

        $resolvedIntro = $this->resolveTemplate($episode->show->intro_template, $episode);

        return view('media_platform.podcasts.planning.finalize_script_wizard.step5', [
            'episode'       => $episode,
            'resolvedIntro' => $resolvedIntro,
        ]);
    }

    /**
     * Prepend the intro text to the script, or skip without modifying it.
     * Accepts _action = 'prepend' | 'skip'.
     */
    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        if ($request->input('_action') === 'prepend') {
            $introText = $request->input('intro_text', '');
            $episode->update(['script' => $introText . "\n\n" . ($episode->script ?? '')]);
        }

        return redirect()->route('podcast_episodes_planning.wizard.finalize.step6');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve {{episode_number}}, {{title}}, and {{sponsors}} placeholders.
     * Sponsor names are listed one per line.
     */
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