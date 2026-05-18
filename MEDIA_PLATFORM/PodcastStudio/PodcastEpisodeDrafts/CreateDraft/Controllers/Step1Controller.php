<?php

// =============================================================================
// Step1Controller — Create Draft Wizard
//
// Step 1: Select a podcast show.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/CreateDraft/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\CreateDraft\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;

class Step1Controller extends Controller
{
    // The five active shows, in display order.
    private const ACTIVE_SHOWS = [
        'The Bob Bloom Show',
        'The Bob Bloom Interviews',
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'PHP Serverless Project Updates',
    ];

    /**
     * Show the podcast show selection list.
     */
    public function show()
    {
        $orderedTitles = self::ACTIVE_SHOWS;

        $shows = PodcastShow::where('user_id', auth()->id())
            ->whereIn('title', $orderedTitles)
            ->get()
            ->sortBy(fn ($show) => array_search($show->title, $orderedTitles))
            ->values();

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.wizard_step1',
            compact('shows')
        );
    }

    /**
     * Store the selected show in the session and redirect to Step 2.
     */
    public function store()
    {
        $validated = request()->validate([
            'podcast_show_id' => ['required', 'integer', 'exists:podcast_shows,id'],
        ]);

        $show = PodcastShow::find($validated['podcast_show_id']);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episode_drafts.create.step1')
                ->with('error', 'Had trouble finding that show in the database. Please try again!');
        }

        session(['wizard.create_draft.podcast_show_id' => $show->id]);

        return redirect()->route('podcast_episode_drafts.create.step2');
    }
}