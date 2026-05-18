<?php

namespace MediaPlatform\Podcasts\Shows\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Shows\Requests\PodcastShowRequest;

use Illuminate\Support\Str;

class PodcastShowController extends Controller
{
    /**
     * Display all podcast shows belonging to the authenticated user.
     */
    public function index()
{
    $manualOrder = [3, 10, 11, 4, 12, 2];

    $shows = PodcastShow::where('user_id', auth()->id())
        ->orderByRaw('array_position(ARRAY[' . implode(',', $manualOrder) . '], id)')
        ->paginate(20);

    return view('media_platform.podcasts.shows.index', compact('shows'));
}
    /**
     * Show the form for creating a new podcast show.
     */
    public function create()
    {
        return view('media_platform.podcasts.shows.create');
    }

    /**
     * Persist a new podcast show for the authenticated user.
     */
    public function store(PodcastShowRequest $request)
    {
        $data         = $request->validated();
        $data['user_id'] = auth()->id();
        $data['slug']    = Str::slug($data['title']);

        // Cast checkboxes — unchecked boxes are absent from the request.
        $data['itunes_explicit'] = $request->boolean('itunes_explicit');
        $data['itunes_block']    = $request->boolean('itunes_block');
        $data['itunes_complete'] = $request->boolean('itunes_complete');
        $data['website_enabled'] = $request->boolean('website_enabled');

        PodcastShow::create($data);

        return redirect()
            ->route('podcast_shows.index')
            ->with('success', 'Podcast show created successfully.');
    }

    /**
     * Display a single podcast show.
     * Ownership check: only the owning user may view their show.
     * Episodes are paginated using the pagination_show config value.
     * Footer links are listed in full, sorted by link_order ascending.
     */
    public function show(PodcastShow $podcast_show)
    {
        abort_if($podcast_show->user_id !== auth()->id(), 403);

        $episodes = $podcast_show->episodes()
            ->orderByDesc('created_at')
            ->paginate(config('admin.pagination_show'));

        $footerLinks = $podcast_show->footerLinks()
            ->orderBy('link_order')
            ->get();

        return view('media_platform.podcasts.shows.show', [
            'show'        => $podcast_show,
            'episodes'    => $episodes,
            'footerLinks' => $footerLinks,
        ]);
    }

    /**
     * Show the form for editing a podcast show.
     */
    public function edit(PodcastShow $podcast_show)
    {
        abort_if($podcast_show->user_id !== auth()->id(), 403);

        return view('media_platform.podcasts.shows.edit', ['show' => $podcast_show]);
    }

    /**
     * Persist updates to a podcast show.
     */
    public function update(PodcastShowRequest $request, PodcastShow $podcast_show)
    {
        abort_if($podcast_show->user_id !== auth()->id(), 403);

        $data         = $request->validated();
        $data['slug'] = Str::slug($data['title']);

        // Cast checkboxes — unchecked boxes are absent from the request.
        $data['itunes_explicit'] = $request->boolean('itunes_explicit');
        $data['itunes_block']    = $request->boolean('itunes_block');
        $data['itunes_complete'] = $request->boolean('itunes_complete');
        $data['website_enabled'] = $request->boolean('website_enabled');

        $podcast_show->update($data);

        return redirect()
            ->route('podcast_shows.show', $podcast_show)
            ->with('success', 'Podcast show updated successfully.');
    }

    /**
     * Show the delete confirmation page for a podcast show.
     */
    public function deleteConfirm(PodcastShow $podcast_show)
    {
        abort_if($podcast_show->user_id !== auth()->id(), 403);

        return view('media_platform.podcasts.shows.delete_confirm', ['show' => $podcast_show]);
    }

    /**
     * Delete a podcast show.
     * Blocked if the show has any episodes — episodes must be removed first.
     */
    public function destroy(PodcastShow $podcast_show)
    {
        abort_if($podcast_show->user_id !== auth()->id(), 403);

        if ($podcast_show->episodes()->exists()) {
            return redirect()
                ->route('podcast_shows.delete.confirm', $podcast_show)
                ->with('error', 'This show cannot be deleted because it has episodes. Please delete all episodes first.');
        }

        $podcast_show->delete();

        return redirect()
            ->route('podcast_shows.index')
            ->with('success', 'Podcast show deleted successfully.');
    }
}