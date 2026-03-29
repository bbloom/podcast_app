<?php

namespace MediaPlatform\PodcastStudio\Management\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use MediaPlatform\PodcastStudio\Management\Requests\PodcastGuestRequest;

class PodcastGuestController extends Controller
{
    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Display all podcast guests.
     * Sortable by 'id' (default, descending) or 'full_name' (ascending).
     */
    public function index()
    {
        $allowedSorts = ['id', 'full_name'];

        $sort      = in_array(request('sort'), $allowedSorts) ? request('sort') : 'id';
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';

        $guests = PodcastGuest::orderBy($sort, $direction)
            ->paginate(config('admin.pagination_show'))
            ->withQueryString();

        return view('media_platform.podcast_studio.management.podcast_guests.index', compact('guests', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new podcast guest.
     */
    public function create()
    {
        return view('media_platform.podcast_studio.management.podcast_guests.create');
    }

    /**
     * Persist a new podcast guest.
     */
    public function store(PodcastGuestRequest $request)
    {
        $guest = PodcastGuest::create($request->validated());

        return redirect()
            ->route('podcast_guests.show', $guest)
            ->with('success', 'Guest created successfully.');
    }

    /**
     * Display a single podcast guest.
     */
    public function show(PodcastGuest $podcast_guest)
    {
        return view(
            'media_platform.podcast_studio.management.podcast_guests.show',
            ['guest' => $podcast_guest]
        );
    }

    /**
     * Show the form for editing an existing podcast guest.
     */
    public function edit(PodcastGuest $podcast_guest)
    {
        return view(
            'media_platform.podcast_studio.management.podcast_guests.edit',
            ['guest' => $podcast_guest]
        );
    }

    /**
     * Persist updates to an existing podcast guest.
     */
    public function update(PodcastGuestRequest $request, PodcastGuest $podcast_guest)
    {
        $podcast_guest->update($request->validated());

        return redirect()
            ->route('podcast_guests.show', $podcast_guest)
            ->with('success', 'Guest updated successfully.');
    }

    /**
     * Show the delete confirmation page for a podcast guest.
     */
    public function deleteConfirm(PodcastGuest $podcast_guest)
    {
        return view(
            'media_platform.podcast_studio.management.podcast_guests.delete_confirm',
            ['guest' => $podcast_guest]
        );
    }

    /**
     * Delete a podcast guest.
     * Blocked if the guest is currently attached to any episode.
     */
    public function destroy(PodcastGuest $podcast_guest)
    {
        if ($podcast_guest->episodes()->exists()) {
            return redirect()
                ->route('podcast_guests.delete.confirm', $podcast_guest)
                ->with('error', 'This guest cannot be deleted because they are attached to one or more episodes. Please detach them first.');
        }

        $podcast_guest->delete();

        return redirect()
            ->route('podcast_guests.index')
            ->with('success', 'Guest deleted successfully.');
    }

    // =========================================================================
    // Attach / Detach from guest show view
    // =========================================================================

    /**
     * Show the attach page for a given guest — lists episodes not yet attached.
     */
    public function attachEpisodeIndex(PodcastGuest $podcast_guest)
    {
        $attachedIds = $podcast_guest->episodes()->pluck('podcast_episodes.id');

        $episodes = PodcastEpisode::whereNotIn('id', $attachedIds)
            ->orderBy('title')
            ->paginate(config('admin.pagination_show'));

        return view(
            'media_platform.podcast_studio.management.podcast_guests.attach_episode',
            ['guest' => $podcast_guest, 'episodes' => $episodes]
        );
    }

    /**
     * Attach an episode to a guest.
     */
    public function attachEpisode(PodcastGuest $podcast_guest, PodcastEpisode $podcast_episode)
    {
        $podcast_guest->episodes()->syncWithoutDetaching([$podcast_episode->id]);

        return redirect()
            ->route('podcast_guests.show', $podcast_guest)
            ->with('success', 'Episode attached successfully.');
    }

    /**
     * Detach an episode from a guest (from the guest show view).
     */
    public function detachEpisode(PodcastGuest $podcast_guest, PodcastEpisode $podcast_episode)
    {
        $podcast_guest->episodes()->detach($podcast_episode->id);

        return redirect()
            ->route('podcast_guests.show', $podcast_guest)
            ->with('success', 'Episode detached successfully.');
    }

    // =========================================================================
    // Attach / Detach from episode show view
    // =========================================================================

    /**
     * Show the attach page for a given episode — lists guests not yet attached.
     */
    public function attachGuestIndex(PodcastEpisode $podcast_episode)
    {
        $attachedIds = $podcast_episode->guests()->pluck('podcast_guests.id');

        $guests = PodcastGuest::where('enabled', true)
            ->whereNotIn('id', $attachedIds)
            ->orderBy('full_name')
            ->paginate(config('admin.pagination_show'));

        return view(
            'media_platform.podcast_studio.management.podcast_guests.attach_guest',
            ['episode' => $podcast_episode, 'guests' => $guests]
        );
    }

    /**
     * Attach a guest to an episode (from the episode show view).
     */
    public function attachGuest(PodcastEpisode $podcast_episode, PodcastGuest $podcast_guest)
    {
        $podcast_episode->guests()->syncWithoutDetaching([$podcast_guest->id]);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Guest attached successfully.');
    }

    /**
     * Detach a guest from an episode (from the episode show view).
     */
    public function detachGuest(PodcastEpisode $podcast_episode, PodcastGuest $podcast_guest)
    {
        $podcast_episode->guests()->detach($podcast_guest->id);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Guest detached successfully.');
    }
}