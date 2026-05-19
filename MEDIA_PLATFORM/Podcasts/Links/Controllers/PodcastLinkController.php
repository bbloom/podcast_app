<?php

namespace MediaPlatform\Podcasts\Links\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Links\Requests\PodcastLinkRequest;
use Illuminate\Http\RedirectResponse;

class PodcastLinkController extends Controller
{
    // =========================================================================
    // Ownership
    // =========================================================================

    /**
     * Verify that the link belongs to the authenticated user.
     * Returns a redirect with a friendly error if ownership fails.
     */
    private function authorizeOwnership(PodcastLink $link): ?RedirectResponse
    {
        if ($link->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_links.index')
                ->with('error', 'You do not have permission to access that link.');
        }

        return null;
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Display all podcast links belonging to the authenticated user.
     * Sortable by 'id' (default, descending) or 'title' (ascending).
     */
    public function index()
    {
        $allowedSorts = ['id', 'title'];

        $sort      = in_array(request('sort'), $allowedSorts) ? request('sort') : 'id';
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';

        $links = PodcastLink::where('user_id', auth()->id())
            ->orderBy($sort, $direction)
            ->paginate(config('admin.pagination_show'))
            ->withQueryString();

        return view('media_platform.podcasts.links.index', compact('links', 'sort', 'direction'));
    }

    /**
     * Show the form for creating a new podcast link.
     */
    public function create()
    {
        return view('media_platform.podcasts.links.create');
    }

    /**
     * Persist a new podcast link.
     *
     * Attempts to populate title and description automatically via the
     * Embed package. The link is always saved first regardless of the
     * outcome. If the title is populated, the user is sent to the show
     * view. If not (or if Embed throws), the user is sent to the edit
     * view with a warning so they can fill in the title manually.
     */
    public function store(PodcastLinkRequest $request)
    {
        // ── Duplicate checks ──────────────────────────────────────────────
        $existingByUrl = PodcastLink::where('link', $request->input('link'))->first();

        if ($existingByUrl) {
            return redirect()
                ->route('podcast_links.create')
                ->withInput()
                ->with('warning', "This URL already exists as link #{$existingByUrl->id}: \"{$existingByUrl->title}\".");
        }

        $title       = null;
        $description = null;

        // ── Attempt to scrape title and description via Embed ─────────────
        try {
            $embed       = new \Embed\Embed();
            $info        = $embed->get($request->input('link'));
            $title       = $this->prependTitleWithProvider($info->title, $info->providerName);
            $description = $info->description ?? null;
        } catch (\Throwable $e) {
            // Network failure, invalid URL, or any other Embed exception.
            // Fall through with nulls — the user will fill in via edit view.
        }

        $link = PodcastLink::create([
            'user_id'     => auth()->id(),
            'link'        => $request->input('link'),
            'title'       => $title,
            'description' => $description,
            'enabled'     => $request->boolean('enabled'),
        ]);

        if ($title) {
            return redirect()
                ->route('podcast_links.show', $link)
                ->with('success', 'Link created successfully.');
        }

        return redirect()
            ->route('podcast_links.edit', $link)
            ->with('warning', 'Link saved, but the title could not be fetched automatically. Please fill it in.');
    }

    /**
     * Display a single podcast link.
     */
    public function show(PodcastLink $podcast_link)
    {
        if ($redirect = $this->authorizeOwnership($podcast_link)) {
            return $redirect;
        }

        return view('media_platform.podcasts.links.show', ['link' => $podcast_link]);
    }

    /**
     * Show the form for editing an existing podcast link.
     */
    public function edit(PodcastLink $podcast_link)
    {
        if ($redirect = $this->authorizeOwnership($podcast_link)) {
            return $redirect;
        }

        return view('media_platform.podcasts.links.edit', ['link' => $podcast_link]);
    }

    /**
     * Persist updates to an existing podcast link.
     */
    public function update(PodcastLinkRequest $request, PodcastLink $podcast_link)
    {
        if ($redirect = $this->authorizeOwnership($podcast_link)) {
            return $redirect;
        }

        $podcast_link->update($request->validated());

        return redirect()
            ->route('podcast_links.index')
            ->with('success', 'Link updated successfully.');
    }

    /**
     * Show the delete confirmation page for a podcast link.
     */
    public function deleteConfirm(PodcastLink $podcast_link)
    {
        if ($redirect = $this->authorizeOwnership($podcast_link)) {
            return $redirect;
        }

        return view('media_platform.podcasts.links.delete_confirm', ['link' => $podcast_link]);
    }

    /**
     * Delete a podcast link.
     * Blocked if the link is currently attached to any episode.
     */
    public function destroy(PodcastLink $podcast_link)
    {
        if ($redirect = $this->authorizeOwnership($podcast_link)) {
            return $redirect;
        }

        if ($podcast_link->episodes()->exists()) {
            return redirect()
                ->route('podcast_links.delete.confirm', $podcast_link)
                ->with('error', 'This link cannot be deleted because it is attached to one or more episodes. Please detach it first.');
        }

        $podcast_link->delete();

        return redirect()
            ->route('podcast_links.index')
            ->with('success', 'Link deleted successfully.');
    }

    // =========================================================================
    // Attach / Detach
    // =========================================================================

    /**
     * Show the attach page for a given episode.
     * Lists all enabled links not yet attached to the episode, sorted by title
     * ascending, paginated.
     */
    public function attachIndex(PodcastEpisode $podcast_episode)
    {
        $attachedIds = $podcast_episode->links()->pluck('podcast_links.id');

        $links = PodcastLink::where('enabled', true)
            ->whereNotIn('id', $attachedIds)
            ->orderBy('title')
            ->paginate(config('admin.pagination_show'));

        return view(
            'media_platform.podcasts.links.attach',
            ['episode' => $podcast_episode, 'links' => $links]
        );
    }

    /**
     * Attach a podcast link to an episode.
     */
    public function attach(PodcastEpisode $podcast_episode, PodcastLink $podcast_link)
    {
        $podcast_episode->links()->syncWithoutDetaching([$podcast_link->id]);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Link attached successfully.');
    }

    /**
     * Detach a podcast link from an episode.
     */
    public function detach(PodcastEpisode $podcast_episode, PodcastLink $podcast_link)
    {
        $podcast_episode->links()->detach($podcast_link->id);

        return redirect()
            ->route('podcast_episodes.show', $podcast_episode)
            ->with('success', 'Link detached successfully.');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Prepend the provider name to the title if available.
     * e.g. "YouTube: My Video Title"
     */
    private function prependTitleWithProvider(?string $title, ?string $provider): ?string
    {
        if (! $title) {
            return null;
        }

        return $provider ? "{$provider}: {$title}" : $title;
    }
}