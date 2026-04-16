<?php

namespace MediaPlatform\PodcastStudio\Management\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
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
        $episodes = PodcastEpisode::forUser(auth()->id())
            ->with(['show'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('media_platform.podcast_studio.management.podcast_episodes.index', compact('episodes'));
    }


    // The CREATE and STORE methods are handled in the CREATE EPISODE wizard


    /**
     * Display a single podcast episode.
     * Ownership check: only the owning user may view their episode.
     */
    public function show(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        $podcast_episode->load(['show']);

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

        // All enum cases are available for selection in the edit form.
        $statuses = PodcastEpisodeStatus::cases();

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

        $podcast_episode->load(['show', 'links', 'guests']);

        $blockingReason = $this->deleteBlockingReason($podcast_episode);

        return view(
            'media_platform.podcast_studio.management.podcast_episodes.delete_confirm',
            ['episode' => $podcast_episode, 'blockingReason' => $blockingReason]
        );
    }

    /**
     * Delete a podcast episode.
     */
    public function destroy(PodcastEpisode $podcast_episode)
    {
        abort_if($podcast_episode->user_id !== auth()->id(), 403);

        if ($reason = $this->deleteBlockingReason($podcast_episode)) {
            return redirect()
                ->route('podcast_episodes.show', $podcast_episode)
                ->with('error', $reason);
        }

        $podcast_episode->delete();

        return redirect()
            ->route('podcast_episodes.index')
            ->with('success', 'Podcast episode deleted successfully.');
    }

    /**
     * Return a blocking reason string if the episode cannot be deleted, or null if it can.
     */
    private function deleteBlockingReason(PodcastEpisode $episode): ?string
    {
        // Status is a cast enum — compare directly against the enum case.
        if ($episode->status === PodcastEpisodeStatus::published) {
            return "This episode cannot be deleted because it is Published.";
        }

        if ($episode->links()->exists()) {
            return "This episode cannot be deleted because it has links attached. Please detach them first.";
        }

        if ($episode->guests()->exists()) {
            return "This episode cannot be deleted because it has guests attached. Please detach them first.";
        }

        return null;
    }
}