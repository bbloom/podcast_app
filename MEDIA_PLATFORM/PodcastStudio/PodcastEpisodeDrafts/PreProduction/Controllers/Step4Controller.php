<?php

// =============================================================================
// Step4Controller — Pre-Production Wizard (Drafts)
//
// Step 4: Finalize website content and excerpt.
// Upon successful completion, the draft status is set to
// pre_production_complete, making it eligible for episode creation.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use Illuminate\Http\Request;

class Step4Controller extends Controller
{
    /**
     * Show the website content finalization form.
     */
    public function show()
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $draft->load('show');

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.pre_production.wizard_step4',
            compact('draft')
        );
    }

    /**
     * Persist website content, set status to pre_production_complete,
     * clear the session, and redirect to the draft show view.
     */
    public function store(Request $request)
    {
        $draft = $this->getDraft();
        if (! $draft) {
            return redirect()->route('draft_pre_production.step1');
        }

        $request->validate([
            'website_content' => ['required', 'string', 'max:10000'],
            'website_excerpt' => ['nullable', 'string', 'max:255'],
        ]);

        $draft->update([
            'website_content' => $request->input('website_content'),
            'website_excerpt' => $request->input('website_excerpt'),
            'status'          => PodcastEpisodeDraftStatus::ready_to_create_production_episode,
        ]);

        session()->forget('wizard.draft_pre_production');

        return redirect()
            ->route('podcast_episode_drafts.show', $draft)
            ->with('success', 'Pre-production complete! This draft is now ready for episode creation.');
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