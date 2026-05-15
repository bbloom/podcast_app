<?php

// =============================================================================
// Step2Controller — Create Draft Wizard
//
// Step 2: Fill in draft fields and persist.
// Displays recent episodes for the selected show as a convenience when
// choosing an episode number.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/CreateDraft/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\CreateDraft\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Requests\PodcastEpisodeDraftRequest;

class Step2Controller extends Controller
{
    /**
     * Show the draft details form.
     */
    public function show()
    {
        $showId = session('wizard.create_draft.podcast_show_id');

        if (! $showId) {
            return redirect()->route('podcast_episode_drafts.create.step1');
        }

        $show = PodcastShow::find($showId);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episode_drafts.create.step1')
                ->with('error', 'Had trouble finding that show in the database. Please try again!');
        }

        // Determine next episode number from existing episodes.
        $lastEpisodeNumber = PodcastEpisode::where('podcast_show_id', $showId)
            ->max('itunes_episode') ?? 0;

        // Also check drafts for this show in case a draft already claims a higher number.
        $lastDraftNumber = PodcastEpisodeDraft::where('podcast_show_id', $showId)
            ->max('episode_number') ?? 0;

        $nextNumber = max($lastEpisodeNumber, $lastDraftNumber) + 1;

        // Recent episodes for this show — convenience reference.
        $recentEpisodes = PodcastEpisode::where('podcast_show_id', $showId)
            ->orderByDesc('itunes_episode')
            ->limit(5)
            ->get();

        // Existing drafts for this show — so the user can see what's already planned.
        $existingDrafts = PodcastEpisodeDraft::where('podcast_show_id', $showId)
            ->forUser(auth()->id())
            ->orderByDesc('episode_number')
            ->get();

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.wizard_step2',
            compact('show', 'nextNumber', 'recentEpisodes', 'existingDrafts')
        );
    }

    /**
     * Validate and persist the new draft, then redirect to the show view.
     */
    public function store(PodcastEpisodeDraftRequest $request)
    {
        $showId = session('wizard.create_draft.podcast_show_id');

        if (! $showId) {
            return redirect()->route('podcast_episode_drafts.create.step1');
        }

        $show = PodcastShow::find($showId);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episode_drafts.create.step1')
                ->with('error', 'Had trouble finding that show in the database. Please try again!');
        }

        $draft = PodcastEpisodeDraft::create(
            array_merge($request->validated(), [
                'user_id' => auth()->id(),
            ])
        );

        // Clear wizard session data.
        session()->forget('wizard.create_draft');

        return redirect()
            ->route('podcast_episode_drafts.show', $draft)
            ->with('success', 'Draft created successfully.');
    }
}