<?php

namespace MediaPlatform\PodcastStudio\PreProduction\CreateEpisode\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class Step2Controller extends Controller
{
    /**
     * Show the episode details form.
     */
    public function show()
    {
        $showId = session('wizard.create_episode.podcast_show_id');

        if (! $showId) {
            return redirect()->route('pre_production_create_podcast_episode.step1');
        }

        $show = PodcastShow::find($showId);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('pre_production_create_podcast_episode.step1')
                ->with('error', 'Had trouble finding that show in the database. Please try again!');
        }

        // Determine next episode number.
        $lastNumber   = PodcastEpisode::where('podcast_show_id', $showId)->max('itunes_episode') ?? 0;
        $nextNumber   = $lastNumber + 1;
        $defaultTitle = "#{$nextNumber} - Title To Be Determined";

        // Five most recent episodes for this show, by episode number descending.
        // Status is now a cast enum on the model — no eager-loading needed.
        $recentEpisodes = PodcastEpisode::where('podcast_show_id', $showId)
            ->orderByDesc('itunes_episode')
            ->limit(5)
            ->get();

        return view(
            'media_platform.podcast_studio.pre_production.create_episode.wizard_step2',
            compact('show', 'nextNumber', 'defaultTitle', 'recentEpisodes')
        );
    }
}