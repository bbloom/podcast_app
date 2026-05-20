<?php

namespace MediaPlatform\Podcasts\Planning\EditThemeField\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditThemeFieldController extends Controller
{
    /**
     * Render the theme editor page.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to edit that episode.');
        }

        return view('media_platform.podcasts.planning.edit_theme_field.edit', [
            'episode' => $podcast_episode_planning,
        ]);
    }

    /**
     * Save the theme and return a JSON response.
     * Called by Alpine.js fetch() — the user stays on the page with scroll preserved.
     */
    public function save(Request $request, PodcastEpisodePlanning $podcast_episode_planning): JsonResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $podcast_episode_planning->update(['theme' => $request->input('theme')]);

        return response()->json(['success' => true, 'message' => 'Theme saved.']);
    }

    /**
     * Save the theme and redirect to the episode show page.
     * Called by standard form submit ("Save and Exit").
     */
    public function saveAndExit(Request $request, PodcastEpisodePlanning $podcast_episode_planning): RedirectResponse
    {
        if ($podcast_episode_planning->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to edit that episode.');
        }

        $podcast_episode_planning->update(['theme' => $request->input('theme')]);

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', 'Theme saved.');
    }
}