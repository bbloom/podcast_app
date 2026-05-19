<?php

namespace MediaPlatform\Podcasts\Links\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Links\Requests\PodcastLinkRequest;

class PodcastLinkController extends Controller
{
    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Display all podcast links.
     * Sortable by 'id' (default, descending) or 'title' (ascending).
     * Direction toggles when the same column is clicked again.
     */
    public function index()
    {
        // Allowed sort columns — whitelist to prevent SQL injection.
        $allowedSorts = ['id', 'title'];

        $sort      = in_array(request('sort'), $allowedSorts) ? request('sort') : 'id';
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';

        $links = PodcastLink::orderBy($sort, $direction)
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

        $title = null;
        $description = null;

        // ── Attempt to scrape title and description via Embed ─────────────
        try {
            $embed    = new \Embed\Embed();
            $info     = $embed->get($request->input('link'));
            $title    = $this->prependTitleWithProvider($info->title, $info->providerName);
            $description = $info->description ?? null;
        } catch (\Throwable $e) {
            // Network failure, invalid URL, or any other Embed exception.
            // Fall through with nulls — the user will fill in via edit view.
        }

        // ── Duplicate title check (after Embed) ──────────────────────────
        if (! empty($title)) {
            $existingByTitle = PodcastLink::where('title', $title)->first();

            if ($existingByTitle) {
                return redirect()
                    ->route('podcast_links.create')
                    ->withInput()
                    ->with('warning', "A link with this title already exists as link #{$existingByTitle->id}: \"{$existingByTitle->title}\".");
            }
        }

        // ── Always save the link ──────────────────────────────────────────
        $podcastLink = PodcastLink::create([
            'link'        => $request->input('link'),
            'title'       => $title,
            'description' => $description,
            'enabled'     => true,
        ]);

        // ── Route based on whether we got a title ─────────────────────────
        if (! empty($podcastLink->title)) {
            return redirect()
                ->route('podcast_links.show', $podcastLink)
                ->with('success', 'Link saved successfully.');
        }

        return redirect()
            ->route('podcast_links.edit', $podcastLink)
            ->with('warning', 'The link was saved but the title could not be found automatically. Please enter it manually.');
    }

    // =========================================================================
    // Embed helpers
    // =========================================================================

    /**
     * Prepend the provider name (normalised) to the page title.
     * e.g. "AWS: New for AWS Lambda – Container Image Support"
     */
    private function prependTitleWithProvider(?string $title, ?string $provider): string
    {
        if (empty($title)) {
            return '';
        }

        $prefix = $this->getSiteName($provider ?? '');

        return $prefix ? $prefix . ': ' . $title : $title;
    }

    /**
     * Normalise provider names to preferred short-form labels.
     * Add entries here as new sources are encountered.
     */
    private function getSiteName(string $provider): string
    {
        return match ($provider) {
            'Amazon Web Services' => 'AWS',
            'The JetBrains Blog'  => 'JetBrains',
            ''                    => '',
            default               => $provider,
        };
    }

    /**
     * Display a single podcast link.
     */
    public function show(PodcastLink $podcast_link)
    {
        return view(
            'media_platform.podcasts.links.show',
            ['link' => $podcast_link]
        );
    }

    /**
     * Show the form for editing an existing podcast link.
     */
    public function edit(PodcastLink $podcast_link)
    {
        return view(
            'media_platform.podcasts.links.edit',
            ['link' => $podcast_link]
        );
    }

    /**
     * Persist updates to an existing podcast link.
     */
    public function update(PodcastLinkRequest $request, PodcastLink $podcast_link)
    {
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
        return view(
            'media_platform.podcasts.links.delete_confirm',
            ['link' => $podcast_link]
        );
    }

    /**
     * Delete a podcast link.
     * Blocked if the link is currently attached to any episode.
     */
    public function destroy(PodcastLink $podcast_link)
    {
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
        // Get IDs of links already attached to this episode.
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
        // syncWithoutDetaching prevents duplicate pivot rows.
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
}