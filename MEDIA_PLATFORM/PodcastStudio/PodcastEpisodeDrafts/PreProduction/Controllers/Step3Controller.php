<?php

// =============================================================================
// Step3Controller — Pre-Production Wizard (Drafts)
//
// Step 3: Finalize the draft/script content.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use Illuminate\Http\Request;

class Step3Controller extends Controller
{
    /**
     * Show the draft/script finalization form.
     */
    public function show()
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $draft->load('show');

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.pre_production.wizard_step3',
            compact('draft')
        );
    }

    /**
     * Persist the finalized draft/script.
     */
    public function store(Request $request)
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $request->validate([
            'draft' => ['required', 'string'],
        ]);

        $draft->update($request->only(['draft']));

        return redirect()->route('draft_pre_production.step4');
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