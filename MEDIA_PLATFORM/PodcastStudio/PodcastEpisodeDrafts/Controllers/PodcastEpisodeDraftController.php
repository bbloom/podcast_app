<?php

// =============================================================================
// PodcastEpisodeDraftController
//
// CRUD controller for podcast episode drafts (minus Create, which is handled
// by the Create Draft wizard controllers).
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Models\PodcastEpisodeDraft;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Requests\PodcastEpisodeDraftRequest;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Http\Request;

class PodcastEpisodeDraftController extends Controller
{
    /**
     * Columns the draft index may be sorted by.
     * Keys = allowed query-string values; values = actual DB columns.
     */
    private const SORTABLE_COLUMNS = [
        'id'             => 'podcast_episode_drafts.id',
        'title'          => 'podcast_episode_drafts.title',
        'show'           => 'podcast_shows.title',
        'date'           => 'podcast_episode_drafts.date',
        'episode_number' => 'podcast_episode_drafts.episode_number',
    ];

    /**
     * Display all podcast episode drafts belonging to the authenticated user.
     */
    public function index(Request $request)
    {
        $sortKey = $request->query('sort', 'id');
        $sortDir = $request->query('dir', 'desc');

        if (! array_key_exists($sortKey, self::SORTABLE_COLUMNS)) {
            $sortKey = 'id';
        }
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        $sortColumn = self::SORTABLE_COLUMNS[$sortKey];

        $drafts = PodcastEpisodeDraft::forUser(auth()->id())
            ->with(['show'])
            ->when(
                $sortKey === 'show',
                fn ($q) => $q->select('podcast_episode_drafts.*')
                             ->join('podcast_shows', 'podcast_shows.id', '=', 'podcast_episode_drafts.podcast_show_id')
            )
            ->orderBy($sortColumn, $sortDir)
            ->paginate(20)
            ->appends(['sort' => $sortKey, 'dir' => $sortDir]);

        return view('media_platform.podcast_studio.podcast_episode_drafts.index', [
            'drafts' => $drafts,
            'sort'   => $sortKey,
            'dir'    => $sortDir,
        ]);
    }

    /**
     * Display a single podcast episode draft.
     */
    public function show(PodcastEpisodeDraft $podcast_episode_draft)
    {
        abort_if($podcast_episode_draft->user_id !== auth()->id(), 403);

        $podcast_episode_draft->load(['show', 'links', 'guests']);

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.show',
            ['draft' => $podcast_episode_draft]
        );
    }

    /**
     * Show the form for editing a podcast episode draft.
     */
    public function edit(PodcastEpisodeDraft $podcast_episode_draft)
    {
        abort_if($podcast_episode_draft->user_id !== auth()->id(), 403);

        $shows = PodcastShow::where('user_id', auth()->id())->orderBy('title')->get();

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.edit',
            ['draft' => $podcast_episode_draft, 'shows' => $shows]
        );
    }

    /**
     * Persist updates to a podcast episode draft.
     */
    public function update(PodcastEpisodeDraftRequest $request, PodcastEpisodeDraft $podcast_episode_draft)
    {
        abort_if($podcast_episode_draft->user_id !== auth()->id(), 403);

        // Ownership: ensure the selected show belongs to this user.
        $show = PodcastShow::findOrFail($request->validated()['podcast_show_id']);
        abort_if($show->user_id !== auth()->id(), 403);

        $podcast_episode_draft->update($request->validated());

        return redirect()
            ->route('podcast_episode_drafts.show', $podcast_episode_draft)
            ->with('success', 'Draft updated successfully.');
    }

    /**
     * Show the delete confirmation page for a podcast episode draft.
     */
    public function deleteConfirm(PodcastEpisodeDraft $podcast_episode_draft)
    {
        abort_if($podcast_episode_draft->user_id !== auth()->id(), 403);

        $podcast_episode_draft->load(['show', 'links', 'guests']);

        return view(
            'media_platform.podcast_studio.podcast_episode_drafts.delete_confirm',
            ['draft' => $podcast_episode_draft]
        );
    }

    /**
     * Delete a podcast episode draft.
     */
    public function destroy(PodcastEpisodeDraft $podcast_episode_draft)
    {
        abort_if($podcast_episode_draft->user_id !== auth()->id(), 403);

        $podcast_episode_draft->delete();

        return redirect()
            ->route('podcast_episode_drafts.index')
            ->with('success', 'Draft deleted successfully.');
    }
}