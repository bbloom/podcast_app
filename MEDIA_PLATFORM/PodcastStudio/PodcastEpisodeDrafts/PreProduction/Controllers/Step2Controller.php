<?php

// =============================================================================
// Step2Controller — Pre-Production Wizard (Drafts)
//
// Step 2: Finalize the title, episode number, and scheduled date.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use Illuminate\Http\Request;

class Step2Controller extends Controller
{
    /**
     * Show the title/episode number/date finalization form.
     */
    public function show()
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $draft->load('show');

        // Recent production episodes for reference.
        $recentEpisodes = PodcastEpisode::where('podcast_show_id', $draft->podcast_show_id)
            ->orderByDesc('itunes_episode')
            ->limit(5)
            ->get();

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.pre_production.wizard_step2',
            compact('draft', 'recentEpisodes')
        );
    }

    /**
     * Persist the finalized title, episode number, and date.
     */
    public function store(Request $request)
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'date'           => ['required', 'date'],
        ]);

        $draft->update($request->only(['title', 'episode_number', 'date']));

        return redirect()->route('draft_pre_production.step3');
    }

    private function getDraft(): ?PodcastEpisodeDraft
    {
        $draftId = session('wizard.draft_pre_production.draft_id');
        if (! $draftId) {
            return null;
        }

        $draft = PodcastEpisodeDraft::find($draftId);
        if (! $draft || $draft->user_id !== auth()->id()) {
            return null;
        }

        return $draft;
    }
}