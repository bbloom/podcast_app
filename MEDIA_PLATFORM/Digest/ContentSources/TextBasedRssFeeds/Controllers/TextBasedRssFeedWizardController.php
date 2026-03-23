<?php

namespace MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\Traits\ManagesListSources;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\Services\RssFeedService;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Http\Request;

class TextBasedRssFeedWizardController extends Controller
{
    // Pull in the shared attach / update / detach logic
    use ManagesListSources;

    public function __construct(private RssFeedService $rssFeedService) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index()
    {
        $feeds = TextBasedRssFeed::where('user_id', auth()->id())
            ->orderBy('title')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.digest.content_sources.text_based_rss_feeds.index', compact('feeds'));
    }

    // -------------------------------------------------------------------------
    // Step 1: Enter RSS URL
    // -------------------------------------------------------------------------

    public function step1()
    {
        return view('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step1');
    }

    public function step1Submit(Request $request)
    {
        $request->validate([
            'rss_url' => ['required', 'url', 'max:500'],
        ], [
            'rss_url.required' => 'Please enter the RSS feed URL.',
            'rss_url.url'      => 'Please enter a valid URL, including https://.',
        ]);

        $rssUrl = $request->input('rss_url');

        // Check if the user has already added this feed
        $exists = TextBasedRssFeed::where('user_id', auth()->id())
            ->where('rss_url', $rssUrl)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['rss_url' => 'You have already added this RSS feed.']);
        }

        // Fetch and parse the RSS feed
        $result = $this->rssFeedService->fetch($rssUrl);

        if (! $result['success']) {
            return back()
                ->withInput()
                ->withErrors(['rss_url' => $result['message']]);
        }

        $request->session()->put('rss_wizard.rss_url',   $rssUrl);
        $request->session()->put('rss_wizard.feed_data', $result['data']);

        return redirect()->route('text_based_rss_feeds.create.step2');
    }

    // -------------------------------------------------------------------------
    // Step 2: Confirm feed details
    // -------------------------------------------------------------------------

    public function step2(Request $request)
    {
        if (! $request->session()->has('rss_wizard.rss_url')) {
            return redirect()->route('text_based_rss_feeds.create.step1');
        }

        $feed = $request->session()->get('rss_wizard.feed_data');

        if (! $feed) {
            return redirect()->route('text_based_rss_feeds.create.step1');
        }

        return view('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step2', compact('feed'));
    }

    public function step2Submit(Request $request)
    {
        if (! $request->session()->has('rss_wizard.rss_url')) {
            return redirect()->route('text_based_rss_feeds.create.step1');
        }

        $request->session()->put('rss_wizard.confirmed', true);

        return redirect()->route('text_based_rss_feeds.create.step3');
    }

    // -------------------------------------------------------------------------
    // Step 3: Assign to lists + set processing mode
    // -------------------------------------------------------------------------

    public function step3(Request $request)
    {
        if (! $request->session()->get('rss_wizard.confirmed')) {
            return redirect()->route('text_based_rss_feeds.create.step1');
        }

        $lists = ListModel::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $feedData  = $request->session()->get('rss_wizard.feed_data', []);
        $feedTitle = $feedData['title'] ?? 'this feed';

        return view('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step3', compact('lists', 'feedTitle'));
    }

    public function step3Submit(Request $request)
    {
        if (! $request->session()->get('rss_wizard.confirmed')) {
            return redirect()->route('text_based_rss_feeds.create.step1');
        }

        $feedData = $request->session()->get('rss_wizard.feed_data');

        if (! $feedData) {
            return redirect()->route('text_based_rss_feeds.create.step1');
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

        // Persist the TextBasedRssFeed record
        $feed = TextBasedRssFeed::create([
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

            $feed->listSources()->create([
                'list_id'         => $listId,
                'enabled'         => true,
                'suspended'       => false,
                'processing_mode' => $mode,
                'search_terms'    => $mode === 'search' ? ($searchTerms[$listId] ?? null) : null,
            ]);
        }

        $title = $feedData['title'] ?? 'Feed';

        // Clear wizard session data before redirecting to done screen
        $request->session()->forget([
            'rss_wizard.rss_url',
            'rss_wizard.feed_data',
            'rss_wizard.confirmed',
        ]);

        $request->session()->put('rss_wizard.saved_title',      $title);
        $request->session()->put('rss_wizard.saved_list_count', count($listIds));

        return redirect()->route('text_based_rss_feeds.create.step4');
    }

    // -------------------------------------------------------------------------
    // Step 4: Done
    // -------------------------------------------------------------------------

    public function step4(Request $request)
    {
        $title     = $request->session()->pull('rss_wizard.saved_title', 'Feed');
        $listCount = $request->session()->pull('rss_wizard.saved_list_count', 0);

        return view('media_platform.digest.content_sources.text_based_rss_feeds.wizard-step4', compact('title', 'listCount'));
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return view('media_platform.digest.content_sources.text_based_rss_feeds.edit', compact('textBasedRssFeed'));
    }

    public function update(Request $request, TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $textBasedRssFeed->update([
            'enabled' => $request->boolean('enabled'),
        ]);

        return redirect()->route('text_based_rss_feeds.index')
            ->with('success', 'Feed updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single text-based RSS feed with its list memberships and tracking status.
     * Also passes the user's lists (excluding already-attached ones) for the attach form.
     */
    public function show(TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        $listSources = $textBasedRssFeed->listSources()
            ->with('list')
            ->paginate(config('admin.pagination_show'));

        $tracking = \MediaPlatform\Digest\Processing\Models\ListSourceTracking::whereIn(
            'list_source_id',
            $listSources->pluck('id')
        )->get()->keyBy('list_source_id');

        // Lists available to attach to — exclude lists this feed is already in
        $attachedListIds = $textBasedRssFeed->listSources()->pluck('list_id')->toArray();

        $availableLists = ListModel::where('user_id', auth()->id())
            ->whereNotIn('id', $attachedListIds)
            ->orderBy('name')
            ->get();

        return view('media_platform.digest.content_sources.text_based_rss_feeds.show', compact(
            'textBasedRssFeed',
            'listSources',
            'tracking',
            'availableLists',
        ));
    }

    // -------------------------------------------------------------------------
    // List Source — attach / update / detach
    // -------------------------------------------------------------------------

    /**
     * Attach this feed to a list.
     */
    public function attachList(Request $request, TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return $this->handleAttach($request, $textBasedRssFeed, 'text_based_rss_feeds.show');
    }

    /**
     * Update processing_mode / search_terms for one list_source row.
     */
    public function updateListSource(Request $request, TextBasedRssFeed $textBasedRssFeed, ListSource $listSource)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return $this->handleUpdateListSource($request, $textBasedRssFeed, $listSource, 'text_based_rss_feeds.show');
    }

    /**
     * Show the detach confirmation page.
     */
    public function detachConfirm(TextBasedRssFeed $textBasedRssFeed, ListSource $listSource)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return $this->handleDetachConfirm($textBasedRssFeed, $listSource, 'media_platform.digest.content_sources.text_based_rss_feeds.detach-confirm');
    }

    /**
     * Delete the list_source row (cascade removes summaries + tracking).
     */
    public function detach(TextBasedRssFeed $textBasedRssFeed, ListSource $listSource)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return $this->handleDetach($textBasedRssFeed, $listSource, 'text_based_rss_feeds.show');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function confirmDelete(TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        return view('media_platform.digest.content_sources.text_based_rss_feeds.delete-confirm', compact('textBasedRssFeed'));
    }

    public function destroy(TextBasedRssFeed $textBasedRssFeed)
    {
        $this->authorizeOwnership($textBasedRssFeed);

        $textBasedRssFeed->delete();

        return redirect()->route('text_based_rss_feeds.index')
            ->with('success', 'Feed deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Abort with 403 if the authenticated user does not own this feed.
     */
    private function authorizeOwnership(TextBasedRssFeed $textBasedRssFeed): void
    {
        abort_if($textBasedRssFeed->user_id !== auth()->id(), 403);
    }
}