<?php

namespace MediaPlatform\PodcastStudio\Management\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisodeStatusLookup;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\Management\Requests\PodcastEpisodeRequest;

use Illuminate\Support\Str;

class PodcastEpisodeController extends Controller
{
    /**
     * Display all podcast episodes belonging to the authenticated user,
     * across all their shows.
     */
    public function index()
    {
        $episodes = PodcastEpisode::where('user_id', auth()->id())
            ->with(['show', 'status'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('media_platform.podcast_studio.management.podcast_episodes.index', compact('episodes'));
    }

    /**
     * Show the form for creating a new podcast episode.
     * Passes the authenticated user's shows and all enabled statuses.
     */
    public function create()
    {
        $shows    = PodcastShow::where('user_id', auth()->id())->orderBy('title')->get();
        $statuses = PodcastEpisodeStatusLookup::where('enabled', true)->orderBy('title')->get();

        return view(
            'media_platform.podcast_studio.management.podcast_episodes.create',
            compact('shows', 'statuses')
        );
    }

    /**
     * Persist a new podcast episode for the authenticated user.
     */
    public function store(PodcastEpisodeRequest $request)
    {
        // Ownership: ensure the selected show belongs to this user.
        $show = PodcastShow::findOrFail($request->validated()['podcast_show_id']);
        abort_if($show->user_id !== auth()->id(), 403);

        $data            = $request->validated();
        $data['user_id'] = auth()->id();
        $data['slug']    = Str::slug($data['title']);

        // Cast checkboxes — unchecked boxes are absent from the request.
        $data['itunes_explicit']  = $request->boolean('itunes_explicit');
        $data['itunes_block']     = $request->boolean('itunes_block');
        $data['rss_feed_enabled'] = $request->boolean('rss_feed_enabled');
        $data['website_enabled']  = $request->boolean('website_enabled');

        PodcastEpisode::create($data);

        return redirect()
            ->route('podcast_episodes.index')
            ->with('success', 'Podcast episode created successfully.');
    }

    /**
     * Display a single podcast episode.
     * Ownership check: only the owning user may view their episode.
     */
    public function show(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        $podcast_episode->load(['show', 'status']);

        return view(
            'media_platform.podcast_studio.management.podcast_episodes.show',
            ['episode' => $podcast_episode]
        );
    }

    /**
     * Show the form for editing a podcast episode.
     */
    public function edit(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        $shows    = PodcastShow::where('user_id', auth()->id())->orderBy('title')->get();
        $statuses = PodcastEpisodeStatusLookup::where('enabled', true)->orderBy('title')->get();

        return view(
            'media_platform.podcast_studio.management.podcast_episodes.edit',
            compact('shows', 'statuses') + ['episode' => $podcast_episode]
        );
    }

    /**
     * Persist updates to a podcast episode.
     */
    public function update(PodcastEpisodeRequest $request, PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        // Ownership: ensure the selected show belongs to this user.
        $show = PodcastShow::findOrFail($request->validated()['podcast_show_id']);
        abort_if($show->user_id !== auth()->id(), 403);

        $data         = $request->validated();
        $data['slug'] = Str::slug($data['title']);

        // Cast checkboxes — unchecked boxes are absent from the request.
        $data['itunes_explicit']  = $request->boolean('itunes_explicit');
        $data['itunes_block']     = $request->boolean('itunes_block');
        $data['rss_feed_enabled'] = $request->boolean('rss_feed_enabled');
        $data['website_enabled']  = $request->boolean('website_enabled');

        $podcast_episode->update($data);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Podcast episode updated successfully.');
    }

    /**
     * Show the delete confirmation page for a podcast episode.
     */
    public function deleteConfirm(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        return view(
            'media_platform.podcast_studio.management.podcast_episodes.delete_confirm',
            ['episode' => $podcast_episode]
        );
    }

    /**
     * Delete a podcast episode.
     */
    public function destroy(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        $podcast_episode->delete();

        return redirect()
            ->route('podcast_episodes.index')
            ->with('success', 'Podcast episode deleted successfully.');
    }
}