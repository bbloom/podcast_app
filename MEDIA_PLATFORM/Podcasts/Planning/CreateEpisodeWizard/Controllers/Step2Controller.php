<?php

namespace MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Step2Controller extends Controller
{
    // -------------------------------------------------------------------------
    // The five active shows, in display order.
    // Only shows matching this list are offered in the wizard.
    // -------------------------------------------------------------------------
    private const ACTIVE_SHOWS = [
        'The Bob Bloom Show',
        'The Bob Bloom Interviews',
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'PHP Serverless Project Updates',
    ];

    /**
     * Render the show-selection step.
     * Loads the user's shows filtered and ordered by ACTIVE_SHOWS.
     */
    public function show(): View
    {
        $shows = PodcastShow::where('user_id', auth()->id())
            ->whereIn('title', self::ACTIVE_SHOWS)
            ->get()
            ->sortBy(fn ($s) => array_search($s->title, self::ACTIVE_SHOWS))
            ->values();

        return view('media_platform.podcasts.planning.create_episode_wizard.step2', compact('shows'));
    }

    /**
     * Store the selected show in the wizard session and advance to Step 3.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'podcast_show_id' => ['required', 'integer', Rule::exists('podcast_shows', 'id')],
        ]);

        $show = PodcastShow::findOrFail($request->input('podcast_show_id'));

        // Ownership check — user must own the show.
        if ($show->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.wizard.create.step2')
                ->with('error', 'You do not have permission to use that show.');
        }

        // Active shows check — must be one of the five active shows.
        if (! in_array($show->title, self::ACTIVE_SHOWS, true)) {
            return redirect()
                ->route('podcast_episodes_planning.wizard.create.step2')
                ->with('error', 'Please select a valid active show.');
        }

        session(['wizard.create_episode_planning.podcast_show_id' => $show->id]);

        return redirect()->route('podcast_episodes_planning.wizard.create.step3');
    }
}