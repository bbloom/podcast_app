<?php

// =============================================================================
// Step1Controller — Pre-Production Wizard (Drafts)
//
// Step 1: Select a podcast show, then select a draft to take through
// pre-production.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/PreProduction/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\PreProduction\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;

class Step1Controller extends Controller
{
    private const ACTIVE_SHOWS = [
        'The Bob Bloom Show',
        'The Bob Bloom Interviews',
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'PHP Serverless Project Updates',
    ];

    /**
     * Show the podcast shows, each with their drafts in "drafting" status.
     */
    public function show()
    {
        $orderedTitles = self::ACTIVE_SHOWS;

        $shows = PodcastShow::where('user_id', auth()->id())
            ->whereIn('title', $orderedTitles)
            ->with(['drafts' => function ($query) {
                $query->where('status', PodcastEpisodeDraftStatus::working_on_draft)
                      ->orderBy('episode_number')
                      ->orderBy('title');
            }])
            ->get()
            ->sortBy(fn ($show) => array_search($show->title, $orderedTitles))
            ->values();

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.pre_production.wizard_step1',
            compact('shows')
        );
    }

    /**
     * Store the selected draft in the session and redirect to Step 2.
     */
    public function store()
    {
        $validated = request()->validate([
            'podcast_episode_draft_id' => ['required', 'integer', 'exists:podcast_episode_drafts,id'],
        ]);

        $draft = PodcastEpisodeDraft::find($validated['podcast_episode_draft_id']);

        if (! $draft || $draft->user_id !== auth()->id()) {
            return redirect()
                ->route('draft_pre_production.step1')
                ->with('error', 'Had trouble finding that draft. Please try again!');
        }

        session(['wizard.draft_pre_production.draft_id' => $draft->id]);

        return redirect()->route('draft_pre_production.step2');
    }
}