<?php

namespace MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class Step1Controller extends Controller
{
    // The five active shows, in display order.
    private const ACTIVE_SHOWS = [
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'The Bob Bloom Interviews',
        'The Bob Bloom Show',
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
            'media_platform.podcast_studio.pre_production.create_episode.wizard_step1',
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
                ->route('pre_production_create_podcast_episode.step1')
                ->with('error', 'Had trouble finding that show in the database. Please try again!');
        }

        session(['wizard.create_episode.podcast_show_id' => $show->id]);

        return redirect()->route('pre_production_create_podcast_episode.step2');
    }
}