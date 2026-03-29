<?php

namespace MediaPlatform\PodcastStudio\Management\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisodeStatusLookup;
use MediaPlatform\PodcastStudio\Management\Requests\PodcastEpisodeStatusLookupRequest;

class PodcastEpisodeStatusLookupController extends Controller
{
    /**
     * Display all status lookup records.
     * Accessible to admins only — enforced via @can('admin') in the view and
     * via the authorize() method on the form request for mutations.
     */
    public function index()
    {
        $statuses = PodcastEpisodeStatusLookup::orderBy('title')->get();

        return view('media_platform.podcast_studio.management.podcast_episode_status_lookup.index', compact('statuses'));
    }

    /**
     * Show the form for creating a new status lookup record.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('admin'), 403);

        return view('media_platform.podcast_studio.management.podcast_episode_status_lookup.create');
    }

    /**
     * Persist a new status lookup record.
     */
    public function store(PodcastEpisodeStatusLookupRequest $request)
    {
        PodcastEpisodeStatusLookup::create($request->validated());

        return redirect()
            ->route('podcast_episode_status_lookup.index')
            ->with('success', 'Status created successfully.');
    }

    /**
     * Display a single status lookup record.
     */
    public function show(PodcastEpisodeStatusLookup $podcast_episode_status_lookup)
    {
        return view(
            'media_platform.podcast_studio.management.podcast_episode_status_lookup.show',
            ['status' => $podcast_episode_status_lookup]
        );
    }
    
    /**
     * Show the form for editing an existing status lookup record.
     */
    public function edit(PodcastEpisodeStatusLookup $podcast_episode_status_lookup)
    {
        abort_unless(auth()->user()->can('admin'), 403);

        return view(
            'media_platform.podcast_studio.management.podcast_episode_status_lookup.edit',
            ['status' => $podcast_episode_status_lookup]
        );
    }

    /**
     * Persist updates to an existing status lookup record.
     */
    public function update(PodcastEpisodeStatusLookupRequest $request, PodcastEpisodeStatusLookup $podcast_episode_status_lookup)
    {
        $podcast_episode_status_lookup->update($request->validated());

        return redirect()
            ->route('podcast_episode_status_lookup.index')
            ->with('success', 'Status updated successfully.');
    }

    /**
     * Show the delete confirmation page for a status lookup record.
     */
    public function deleteConfirm(PodcastEpisodeStatusLookup $podcast_episode_status_lookup)
    {
        abort_unless(auth()->user()->can('admin'), 403);

        return view(
            'media_platform.podcast_studio.management.podcast_episode_status_lookup.delete_confirm',
            ['status' => $podcast_episode_status_lookup]
        );
    }

    /**
     * Delete a status lookup record.
     * Blocked if any podcast episodes are currently using this status.
     */
    public function destroy(PodcastEpisodeStatusLookup $podcast_episode_status_lookup)
    {
        abort_unless(auth()->user()->can('admin'), 403);

        if ($podcast_episode_status_lookup->episodes()->exists()) {
            return redirect()
                ->route('podcast_episode_status_lookup.delete.confirm', $podcast_episode_status_lookup)
                ->with('error', 'This status cannot be deleted because it is assigned to one or more episodes.');
        }

        $podcast_episode_status_lookup->delete();

        return redirect()
            ->route('podcast_episode_status_lookup.index')
            ->with('success', 'Status deleted successfully.');
    }
}