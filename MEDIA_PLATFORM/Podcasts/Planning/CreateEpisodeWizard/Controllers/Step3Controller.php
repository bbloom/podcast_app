<?php

namespace MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Step3Controller extends Controller
{
    /**
     * Render the episode-details form.
     * Guards against missing or invalid session state.
     */
    public function show(): View|RedirectResponse
    {
        $show = $this->getShowFromSession();

        if (! $show) {
            return redirect()
                ->route('podcast_episodes_planning.wizard.create.step1')
                ->with('error', 'Session expired. Please start again.');
        }

        return view('media_platform.podcasts.planning.create_episode_wizard.step3', compact('show'));
    }

    /**
     * Validate the episode details, create the planning record, clear the
     * session, and advance to the Step 4 confirmation screen.
     */
    public function store(Request $request): RedirectResponse
    {
        $show = $this->getShowFromSession();

        if (! $show) {
            return redirect()->route('podcast_episodes_planning.wizard.create.step1');
        }

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'episode_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'scheduled_date' => ['nullable', 'date'],
            'theme'          => ['nullable', 'string'],
        ]);

        $episode = PodcastEpisodePlanning::create([
            'user_id'         => auth()->id(),
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodePlanningStatus::new_episode_created,
            'title'           => $validated['title'],
            'episode_number'  => $validated['episode_number'] ?? null,
            'scheduled_date'  => $validated['scheduled_date'] ?? null,
            'theme'           => $validated['theme'] ?? null,
        ]);

        session()->forget('wizard.create_episode_planning.podcast_show_id');

        return redirect()->route('podcast_episodes_planning.wizard.create.step4', $episode);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Load the PodcastShow from the wizard session.
     * Returns null if the session key is missing, the show doesn't exist,
     * or the show belongs to another user.
     */
    private function getShowFromSession(): ?PodcastShow
    {
        $showId = session('wizard.create_episode_planning.podcast_show_id');

        if (! $showId) {
            return null;
        }

        $show = PodcastShow::find($showId);

        if (! $show || $show->user_id !== auth()->id()) {
            session()->forget('wizard.create_episode_planning.podcast_show_id');
            return null;
        }

        return $show;
    }
}