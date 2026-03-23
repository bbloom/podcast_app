<?php

namespace MediaPlatform\Digest\ContentSources\Youtube\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\Traits\ManagesListSources;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use MediaPlatform\Digest\ContentSources\Youtube\Services\YoutubeService;
use Illuminate\Http\Request;

class YoutubeChannelWizardController extends Controller
{
    // Pull in the shared attach / update / detach logic
    use ManagesListSources;

    public function __construct(private YoutubeService $youtube) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index()
    {
        $channels = YoutubeChannel::where('user_id', auth()->id())
            ->orderBy('title')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.digest.content_sources.youtube.channels.index', compact('channels'));
    }

    // -------------------------------------------------------------------------
    // Step 1: Enter query (URL, handle, or keywords)
    // -------------------------------------------------------------------------

    public function step1()
    {
        return view('media_platform.digest.content_sources.youtube.channels.wizard-step1');
    }

    public function step1Submit(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ], [
            'query.required' => 'Please enter a channel URL, handle, or search keywords.',
        ]);

        $query  = $request->input('query');
        $parsed = $this->youtube->parseInput($query);

        $results = match ($parsed['type']) {
            'handle'     => $this->youtube->searchByHandle($parsed['value']),
            'channel_id' => $this->youtube->searchByChannelId($parsed['value']),
            'keywords'   => $this->youtube->searchByKeywords($parsed['value']),
            default      => [],
        };

        if (empty($results)) {
            return back()
                ->withInput()
                ->withErrors(['query' => 'No channels found for that query. Please try a different URL, handle, or search term.']);
        }

        // Flag any channels the user has already added
        $existingChannelIds = YoutubeChannel::where('user_id', auth()->id())
            ->pluck('channel_id')
            ->toArray();

        $results = collect($results)->map(function ($channel) use ($existingChannelIds) {
            $channel['already_added'] = in_array($channel['channel_id'], $existingChannelIds);
            return $channel;
        })->toArray();

        $request->session()->put('yt_wizard.query',   $query);
        $request->session()->put('yt_wizard.results', $results);

        return redirect()->route('youtube.channels.create.step2');
    }

    // -------------------------------------------------------------------------
    // Step 2: Select channel from results
    // -------------------------------------------------------------------------

    public function step2(Request $request)
    {
        if (! $request->session()->has('yt_wizard.results')) {
            return redirect()->route('youtube.channels.create.step1');
        }

        $results = $request->session()->get('yt_wizard.results');

        return view('media_platform.digest.content_sources.youtube.channels.wizard-step2', compact('results'));
    }

    public function step2Submit(Request $request)
    {
        $request->validate([
            'channel_id' => ['required', 'string'],
        ], [
            'channel_id.required' => 'Please select a channel.',
        ]);

        // Verify the selected channel_id exists in the session results
        $results   = $request->session()->get('yt_wizard.results', []);
        $channelId = $request->input('channel_id');
        $selected  = collect($results)->firstWhere('channel_id', $channelId);

        if (! $selected) {
            return back()->withErrors(['channel_id' => 'The selected channel is not valid. Please start over.']);
        }

        // Prevent adding a channel the user already has
        if ($selected['already_added'] ?? false) {
            return back()->withErrors(['channel_id' => 'You have already added this channel.']);
        }

        $request->session()->put('yt_wizard.selected_channel_id', $channelId);

        return redirect()->route('youtube.channels.create.step3');
    }

    // -------------------------------------------------------------------------
    // Step 3: Confirm selected channel
    // -------------------------------------------------------------------------

    public function step3(Request $request)
    {
        if (! $request->session()->has('yt_wizard.selected_channel_id')) {
            return redirect()->route('youtube.channels.create.step1');
        }

        $results   = $request->session()->get('yt_wizard.results', []);
        $channelId = $request->session()->get('yt_wizard.selected_channel_id');
        $selected  = collect($results)->firstWhere('channel_id', $channelId);

        if (! $selected) {
            return redirect()->route('youtube.channels.create.step1');
        }

        return view('media_platform.digest.content_sources.youtube.channels.wizard-step3', compact('selected'));
    }

    public function step3Submit(Request $request)
    {
        if (! $request->session()->has('yt_wizard.selected_channel_id')) {
            return redirect()->route('youtube.channels.create.step1');
        }

        $request->session()->put('yt_wizard.confirmed', true);

        return redirect()->route('youtube.channels.create.step4');
    }

    // -------------------------------------------------------------------------
    // Step 4: Assign to lists + set processing mode
    // -------------------------------------------------------------------------

    public function step4(Request $request)
    {
        if (! $request->session()->get('yt_wizard.confirmed', false)) {
            return redirect()->route('youtube.channels.create.step1');
        }

        $lists = ListModel::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        // Pass the channel title to the view so it can be displayed
        $results      = $request->session()->get('yt_wizard.results', []);
        $channelId    = $request->session()->get('yt_wizard.selected_channel_id');
        $selected     = collect($results)->firstWhere('channel_id', $channelId);
        $channelTitle = $selected['title'] ?? 'this channel';

        return view('media_platform.digest.content_sources.youtube.channels.wizard-step4', compact('lists', 'channelTitle'));
    }

    public function step4Submit(Request $request)
    {
        $request->validate([
            'list_ids'   => ['required', 'array', 'min:1'],
            'list_ids.*' => ['integer'],
        ], [
            'list_ids.required' => 'Please assign this channel to at least one list.',
            'list_ids.min'      => 'Please assign this channel to at least one list.',
        ]);

        $processingModes = $request->input('processing_modes', []);
        $searchTerms     = $request->input('search_terms', []);

        // Verify all selected lists belong to this user
        $listIds = ListModel::where('user_id', auth()->id())
            ->whereIn('id', $request->input('list_ids'))
            ->pluck('id')
            ->toArray();

        if (empty($listIds)) {
            return back()->withErrors(['list_ids' => 'The selected lists are not valid.']);
        }

        // Retrieve channel data from session
        $results   = $request->session()->get('yt_wizard.results', []);
        $channelId = $request->session()->get('yt_wizard.selected_channel_id');
        $selected  = collect($results)->firstWhere('channel_id', $channelId);

        if (! $selected) {
            return redirect()->route('youtube.channels.create.step1');
        }

        // Persist the YoutubeChannel record
        $channel = YoutubeChannel::create([
            'user_id'     => auth()->id(),
            'channel_id'  => $selected['channel_id'],
            'title'       => $selected['title'],
            'handle'      => ($selected['handle'] && $selected['handle'] !== '—') ? $selected['handle'] : null,
            'channel_url' => $selected['channel_url'],
            'rss_url'     => $selected['rss_url'],
            'thumbnail'   => $selected['thumbnail'] ?? null,
            'description' => $selected['description'] ?? null,
            'enabled'     => true,
        ]);

        // Create list_sources pivot rows for each selected list
        foreach ($listIds as $listId) {
            $mode = $processingModes[$listId] ?? 'description';

            $channel->listSources()->create([
                'list_id'         => $listId,
                'enabled'         => true,
                'suspended'       => false,
                'processing_mode' => $mode,
                'search_terms'    => $mode === 'search' ? ($searchTerms[$listId] ?? null) : null,
            ]);
        }

        $title = $selected['title'];

        // Clear wizard session data before redirecting to done screen
        $request->session()->forget([
            'yt_wizard.query',
            'yt_wizard.results',
            'yt_wizard.selected_channel_id',
            'yt_wizard.confirmed',
        ]);

        $request->session()->put('yt_wizard.saved_title',      $title);
        $request->session()->put('yt_wizard.saved_list_count', count($listIds));

        return redirect()->route('youtube.channels.create.step5');
    }

    // -------------------------------------------------------------------------
    // Step 5: Done
    // -------------------------------------------------------------------------

    public function step5(Request $request)
    {
        $title     = $request->session()->pull('yt_wizard.saved_title', 'Channel');
        $listCount = $request->session()->pull('yt_wizard.saved_list_count', 0);

        return view('media_platform.digest.content_sources.youtube.channels.wizard-step5', compact('title', 'listCount'));
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(YoutubeChannel $youtubeChannel)
    {
        $this->authorizeOwnership($youtubeChannel);

        return view('media_platform.digest.content_sources.youtube.channels.edit', compact('youtubeChannel'));
    }

    public function update(Request $request, YoutubeChannel $youtubeChannel)
    {
        $this->authorizeOwnership($youtubeChannel);

        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $youtubeChannel->update([
            'enabled' => $request->boolean('enabled'),
        ]);

        return redirect()->route('youtube.channels.index')
            ->with('success', 'Channel updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single YouTube channel with its list memberships and tracking status.
     * Also passes the user's lists (excluding already-attached ones) for the attach form.
     */
    public function show(YoutubeChannel $youtubeChannel)
    {
        abort_if($youtubeChannel->user_id !== auth()->id(), 403);

        $listSources = $youtubeChannel->listSources()
            ->with('list')
            ->paginate(config('admin.pagination_show'));

        $tracking = \MediaPlatform\Digest\Processing\Models\ListSourceTracking::whereIn(
            'list_source_id',
            $listSources->pluck('id')
        )->get()->keyBy('list_source_id');

        // Lists available to attach to — exclude lists this channel is already in
        $attachedListIds = $youtubeChannel->listSources()->pluck('list_id')->toArray();

        $availableLists = ListModel::where('user_id', auth()->id())
            ->whereNotIn('id', $attachedListIds)
            ->orderBy('name')
            ->get();

        return view('media_platform.digest.content_sources.youtube.channels.show', compact(
            'youtubeChannel',
            'listSources',
            'tracking',
            'availableLists',
        ));
    }

    // -------------------------------------------------------------------------
    // List Source — attach / update / detach
    // -------------------------------------------------------------------------

    /**
     * Attach this channel to a list.
     */
    public function attachList(Request $request, YoutubeChannel $youtubeChannel)
    {
        $this->authorizeOwnership($youtubeChannel);

        return $this->handleAttach($request, $youtubeChannel, 'youtube.channels.show');
    }

    /**
     * Update processing_mode / search_terms for one list_source row.
     */
    public function updateListSource(Request $request, YoutubeChannel $youtubeChannel, ListSource $listSource)
    {
        $this->authorizeOwnership($youtubeChannel);

        return $this->handleUpdateListSource($request, $youtubeChannel, $listSource, 'youtube.channels.show');
    }

    /**
     * Show the detach confirmation page.
     */
    public function detachConfirm(YoutubeChannel $youtubeChannel, ListSource $listSource)
    {
        $this->authorizeOwnership($youtubeChannel);

        return $this->handleDetachConfirm($youtubeChannel, $listSource, 'media_platform.digest.content_sources.youtube.channels.detach-confirm');
    }

    /**
     * Delete the list_source row (cascade removes summaries + tracking).
     */
    public function detach(YoutubeChannel $youtubeChannel, ListSource $listSource)
    {
        $this->authorizeOwnership($youtubeChannel);

        return $this->handleDetach($youtubeChannel, $listSource, 'youtube.channels.show');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function confirmDelete(YoutubeChannel $youtubeChannel)
    {
        $this->authorizeOwnership($youtubeChannel);

        return view('media_platform.digest.content_sources.youtube.channels.delete-confirm', compact('youtubeChannel'));
    }

    public function destroy(YoutubeChannel $youtubeChannel)
    {
        $this->authorizeOwnership($youtubeChannel);

        $youtubeChannel->delete();

        return redirect()->route('youtube.channels.index')
            ->with('success', 'Channel deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Abort with 403 if the authenticated user does not own this channel.
     */
    private function authorizeOwnership(YoutubeChannel $youtubeChannel): void
    {
        abort_if($youtubeChannel->user_id !== auth()->id(), 403);
    }
}