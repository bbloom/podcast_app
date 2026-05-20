<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PodcastEpisodePlanningGuestController extends Controller
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
     * Show all enabled guests not yet attached to this planning episode.
     */
    public function attachIndex(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $attachedIds = $podcast_episode_planning->guests()
            ->pluck('podcast_guests.id');

        $guests = PodcastGuest::where('enabled', true)
            ->whereNotIn('id', $attachedIds)
            ->orderBy('full_name')
            ->paginate(config('admin.pagination_show'));

        return view('media_platform.podcasts.planning.crud.attach_guest', [
            'episode' => $podcast_episode_planning,
            'guests'  => $guests,
        ]);
    }

    /**
     * Attach a guest to this planning episode.
     * Idempotent — syncing without detaching prevents duplicates.
     */
    public function attach(
        PodcastEpisodePlanning $podcast_episode_planning,
        PodcastGuest $podcast_guest
    ): RedirectResponse {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->guests()->syncWithoutDetaching([$podcast_guest->id]);

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', "{$podcast_guest->full_name} attached to this episode.");
    }

    /**
     * Detach a guest from this planning episode.
     */
    public function detach(
        PodcastEpisodePlanning $podcast_episode_planning,
        PodcastGuest $podcast_guest
    ): RedirectResponse {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->guests()->detach($podcast_guest->id);

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', "{$podcast_guest->full_name} detached from this episode.");
    }
}