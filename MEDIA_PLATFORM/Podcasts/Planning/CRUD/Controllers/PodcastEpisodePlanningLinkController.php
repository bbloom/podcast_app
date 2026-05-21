<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PodcastEpisodePlanningLinkController extends Controller
{
    // =========================================================================
    // Ownership
    // =========================================================================

    /**
     * Verify the planning episode belongs to the authenticated user.
     */
    private function authorizeOwnership(PodcastEpisodePlanning $episode): ?RedirectResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }
        return null;
    }

    // =========================================================================
    // Attach / Detach
    // =========================================================================

    /**
     * Show all enabled links owned by the user not yet attached to this episode.
     */
    public function attachIndex(Request $request, PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $attachedIds = $podcast_episode_planning->links()
            ->pluck('podcast_links.id');

        $search = $request->input('search');

        $links = PodcastLink::where('enabled', true)
            ->where('user_id', auth()->id())
            ->whereNotIn('id', $attachedIds)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('link', 'like', "%{$search}%");
            }))
            ->orderBy('title')
            ->paginate(config('admin.pagination_show'))
            ->withQueryString();

        return view('media_platform.podcasts.planning.crud.attach_link', [
            'episode' => $podcast_episode_planning,
            'links'   => $links,
        ]);
    }

    /**
     * Attach a link to this planning episode.
     * Idempotent — syncing without detaching prevents duplicates.
     */
    public function attach(
        PodcastEpisodePlanning $podcast_episode_planning,
        PodcastLink $podcast_link
    ): RedirectResponse {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->links()->syncWithoutDetaching([$podcast_link->id]);

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', "Link attached to this episode.");
    }

    /**
     * Detach a link from this planning episode.
     */
    public function detach(
        PodcastEpisodePlanning $podcast_episode_planning,
        PodcastLink $podcast_link
    ): RedirectResponse {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->links()->detach($podcast_link->id);

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', "Link detached from this episode.");
    }
}