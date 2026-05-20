<?php

namespace MediaPlatform\Digest\ContentSources\Podcasts\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\Traits\ManagesListSources;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\Services\RssFeedService;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use Illuminate\Http\Request;

class PodcastWizardController extends Controller
{
    // Pull in the shared attach / update / detach logic
    use ManagesListSources;

    public function __construct(private RssFeedService $rssFeedService) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index()
    {
        $podcasts = Podcast::where('user_id', auth()->id())
            ->orderBy('title')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.digest.content_sources.podcasts.index', compact('podcasts'));
    }

    // -------------------------------------------------------------------------
    // Step 1: Enter RSS URL
    // -------------------------------------------------------------------------

    public function step1()
    {
        return view('media_platform.digest.content_sources.podcasts.wizard-step1');
    }

    public function step1Submit(Request $request)
    {
        $request->validate([
            'rss_url' => ['required', 'url', 'max:500'],
        ], [
            'rss_url.required' => 'Please enter the podcast RSS feed URL.',
            'rss_url.url'      => 'Please enter a valid URL, including https://.',
        ]);

        $rssUrl = $request->input('rss_url');

        // Check if the user has already added this feed
        $exists = Podcast::where('user_id', auth()->id())
            ->where('rss_url', $rssUrl)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['rss_url' => 'You have already added this podcast feed.']);
        }

        // Fetch and parse the RSS feed
        $result = $this->rssFeedService->fetch($rssUrl);

        if (! $result['success']) {
            return back()
                ->withInput()
                ->withErrors(['rss_url' => $result['message']]);
        }

        $request->session()->put('podcast_wizard.rss_url',   $rssUrl);
        $request->session()->put('podcast_wizard.feed_data', $result['data']);

        return redirect()->route('digest-podcasts.create.step2');
    }

    // -------------------------------------------------------------------------
    // Step 2: Confirm feed details
    // -------------------------------------------------------------------------

    public function step2(Request $request)
    {
        if (! $request->session()->has('podcast_wizard.rss_url')) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        $podcast = $request->session()->get('podcast_wizard.feed_data');

        if (! $podcast) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        return view('media_platform.digest.content_sources.podcasts.wizard-step2', compact('podcast'));
    }

    public function step2Submit(Request $request)
    {
        if (! $request->session()->has('podcast_wizard.rss_url')) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        $request->session()->put('podcast_wizard.confirmed', true);

        return redirect()->route('digest-podcasts.create.step3');
    }

    // -------------------------------------------------------------------------
    // Step 3: Assign to lists + set processing mode
    // -------------------------------------------------------------------------

    public function step3(Request $request)
    {
        if (! $request->session()->get('podcast_wizard.confirmed')) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        $lists = ListModel::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $feedData     = $request->session()->get('podcast_wizard.feed_data', []);
        $podcastTitle = $feedData['title'] ?? 'this podcast';

        return view('media_platform.digest.content_sources.podcasts.wizard-step3', compact('lists', 'podcastTitle'));
    }

    public function step3Submit(Request $request)
    {
        if (! $request->session()->get('podcast_wizard.confirmed')) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        $feedData = $request->session()->get('podcast_wizard.feed_data');

        if (! $feedData) {
            return redirect()->route('digest-podcasts.create.step1');
        }

        $listIds         = $request->input('list_ids', []);
        $processingModes = $request->input('processing_modes', []);
        $searchTerms     = $request->input('search_terms', []);

        // Validate that all submitted list_ids belong to the authenticated user
        $validListIds = ListModel::where('user_id', auth()->id())
            ->whereIn('id', $listIds)
            ->pluck('id')
            ->toArray();

        if (! empty($listIds) && count($validListIds) !== count($listIds)) {
            return back()->withErrors(['list_ids' => 'One or more selected lists are invalid.']);
        }

        if (empty($listIds)) {
            return back()->withErrors(['list_ids' => 'Please select at least one list.']);
        }

        // Persist the Podcast record
        $podcast = Podcast::create([
            'user_id'     => auth()->id(),
            'rss_url'     => $feedData['rss_url'],
            'title'       => $feedData['title'],
            'description' => $feedData['description'] ?? null,
            'site_url'    => $feedData['site_url']    ?? null,
            'thumbnail'   => $feedData['thumbnail']   ?? null,
            'enabled'     => true,
        ]);

        // Create list_sources pivot rows for each selected list
        foreach ($listIds as $listId) {
            $mode = $processingModes[$listId] ?? 'description';

            $podcast->listSources()->create([
                'list_id'         => $listId,
                'enabled'         => true,
                'suspended'       => false,
                'processing_mode' => $mode,
                'search_terms'    => $mode === 'search' ? ($searchTerms[$listId] ?? null) : null,
            ]);
        }

        $title = $feedData['title'] ?? 'Podcast';

        // Clear wizard session data before redirecting to done screen
        $request->session()->forget([
            'podcast_wizard.rss_url',
            'podcast_wizard.feed_data',
            'podcast_wizard.confirmed',
        ]);

        $request->session()->put('podcast_wizard.saved_title',      $title);
        $request->session()->put('podcast_wizard.saved_list_count', count($listIds));

        return redirect()->route('digest-podcasts.create.step4');
    }

    // -------------------------------------------------------------------------
    // Step 4: Done
    // -------------------------------------------------------------------------

    public function step4(Request $request)
    {
        $title     = $request->session()->pull('podcast_wizard.saved_title', 'Podcast');
        $listCount = $request->session()->pull('podcast_wizard.saved_list_count', 0);

        return view('media_platform.digest.content_sources.podcasts.wizard-step4', compact('title', 'listCount'));
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        return view('media_platform.digest.content_sources.podcasts.edit', compact('podcast'));
    }

    public function update(Request $request, Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $podcast->update([
            'enabled' => $request->boolean('enabled'),
        ]);

        return redirect()->route('digest-podcasts.index')
            ->with('success', 'Podcast updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single podcast with its list memberships and tracking status.
     * Also passes the user's lists (excluding already-attached ones) for the attach form.
     */
    public function show(Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        $listSources = $podcast->listSources()
            ->with('list')
            ->paginate(config('admin.pagination_show'));

        $tracking = \MediaPlatform\Digest\Processing\Models\ListSourceTracking::whereIn(
            'list_source_id',
            $listSources->pluck('id')
        )->get()->keyBy('list_source_id');

        // Lists available to attach to — exclude lists this podcast is already in
        $attachedListIds = $podcast->listSources()->pluck('list_id')->toArray();

        $availableLists = ListModel::where('user_id', auth()->id())
            ->whereNotIn('id', $attachedListIds)
            ->orderBy('name')
            ->get();

        return view('media_platform.digest.content_sources.podcasts.show', compact(
            'podcast',
            'listSources',
            'tracking',
            'availableLists',
        ));
    }

    // -------------------------------------------------------------------------
    // List Source — attach / update / detach
    // -------------------------------------------------------------------------

    /**
     * Attach this podcast to a list.
     */
    public function attachList(Request $request, Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        return $this->handleAttach($request, $podcast, 'digest-podcasts.show');
    }

    /**
     * Update processing_mode / search_terms for one list_source row.
     */
    public function updateListSource(Request $request, Podcast $podcast, ListSource $listSource)
    {
        $this->authorizeOwnership($podcast);

        return $this->handleUpdateListSource($request, $podcast, $listSource, 'digest-podcasts.show');
    }

    /**
     * Show the detach confirmation page.
     */
    public function detachConfirm(Podcast $podcast, ListSource $listSource)
    {
        $this->authorizeOwnership($podcast);

        return $this->handleDetachConfirm($podcast, $listSource, 'media_platform.digest.content_sources.podcasts.detach-confirm');
    }

    /**
     * Delete the list_source row (cascade removes summaries + tracking).
     */
    public function detach(Podcast $podcast, ListSource $listSource)
    {
        $this->authorizeOwnership($podcast);

        return $this->handleDetach($podcast, $listSource, 'digest-podcasts.show');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function confirmDelete(Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        return view('media_platform.digest.content_sources.podcasts.delete-confirm', compact('podcast'));
    }

    public function destroy(Podcast $podcast)
    {
        $this->authorizeOwnership($podcast);

        $podcast->delete();

        return redirect()->route('digest-podcasts.index')
            ->with('success', 'Podcast deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Abort with 403 if the authenticated user does not own this podcast.
     */
    private function authorizeOwnership(Podcast $podcast): void
    {
        abort_if($podcast->user_id !== auth()->id(), 403);
    }
}