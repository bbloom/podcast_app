<?php

namespace MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Concerns\DerivesPublishedEpisodeFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step2Controller extends Controller
{
    use DerivesPublishedEpisodeFields;

    /**
     * Render the review and edit step.
     * Shows the key inputs that drive all derivations, plus read-only previews
     * of the values that will be derived when publishing. The user can edit
     * and correct anything here before the one-way confirmation step.
     */
    public function show(): View|RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index')
                ->with('error', 'Session expired. Please return to the episode and begin the wizard again.');
        }

        $episode->load('show');
        $show = $episode->show;

        // Compute derived value previews from the current planning record.
        $derived = [
            'formatted_title'        => $this->get_title($episode),
            'slug'                   => $this->get_slug($episode, $show),
            'enclosure_url'          => $this->get_itunes_enclosure_url($episode, $show),
            'raw_audio_filename'     => $this->get_raw_input_audio_filename($episode, $show),
            'itunes_link'            => $this->get_itunes_link($episode, $show),
            'publish_on'             => $this->get_website_publish_on($episode),
        ];

        return view('media_platform.podcasts.planning.prepare_for_publishing_wizard.step2', [
            'episode' => $episode,
            'derived' => $derived,
        ]);
    }

    /**
     * Save any edits back to the planning record and advance to Step 3.
     */
    public function store(Request $request): RedirectResponse
    {
        $episode = $this->getEpisodeFromSession();
        if (! $episode) {
            return redirect()->route('podcast_episodes_planning.index');
        }

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'episode_number'  => ['nullable', 'integer', 'min:1', 'max:9999'],
            'scheduled_date'  => ['nullable', 'date'],
            'website_content' => ['nullable', 'string'],
            'website_excerpt' => ['nullable', 'string', 'max:500'],
        ]);

        // Save any edits back to the planning record before the hard handoff.
        $episode->update($validated);

        return redirect()->route('podcast_episodes_planning.wizard.publish.step3');
    }

    private function getEpisodeFromSession(): ?PodcastEpisodePlanning
    {
        $id = session('wizard.prepare_for_publishing.episode_id');
        if (! $id) return null;
        $episode = PodcastEpisodePlanning::find($id);
        if (! $episode || $episode->user_id !== auth()->id()) return null;
        return $episode;
    }
}