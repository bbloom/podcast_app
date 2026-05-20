<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Planning\CRUD\Requests\PodcastEpisodePlanningRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PodcastEpisodePlanningController extends Controller
{
    // =========================================================================
    // Sortable columns
    // =========================================================================

    /**
     * Columns the index may be sorted by.
     * Keys = allowed query-string values; values = actual DB columns.
     */
    private const SORTABLE_COLUMNS = [
        'id'             => 'podcast_episodes_planning.id',
        'title'          => 'podcast_episodes_planning.title',
        'episode_number' => 'podcast_episodes_planning.episode_number',
        'scheduled_date' => 'podcast_episodes_planning.scheduled_date',
        'status'         => 'podcast_episodes_planning.status',
        'show'           => 'podcast_shows.title',
    ];

    // =========================================================================
    // Ownership
    // =========================================================================

    /**
     * Verify that the planning episode belongs to the authenticated user.
     * Returns a redirect with a friendly error message if ownership fails.
     */
    private function authorizeOwnership(PodcastEpisodePlanning $episode): ?RedirectResponse
    {
        if ($episode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes_planning.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        return null;
    }

    // =========================================================================
    // CRUD actions
    // =========================================================================

    /**
     * Display all planning episodes belonging to the authenticated user.
     * Sortable by id (default desc), title, episode_number, scheduled_date,
     * status, and show title.
     */
    public function index(Request $request): View
    {
        $sortKey = $request->query('sort', 'id');
        $sortDir = $request->query('dir', 'desc');

        // Whitelist sort column and direction.
        if (! array_key_exists($sortKey, self::SORTABLE_COLUMNS)) {
            $sortKey = 'id';
        }
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        $sortColumn = self::SORTABLE_COLUMNS[$sortKey];

        $episodes = PodcastEpisodePlanning::forUser(auth()->id())
            ->with(['show'])
            ->when(
                $sortKey === 'show',
                // Join the shows table so we can sort by the show's title.
                fn ($q) => $q->select('podcast_episodes_planning.*')
                             ->join('podcast_shows', 'podcast_shows.id', '=', 'podcast_episodes_planning.podcast_show_id')
            )
            ->orderBy($sortColumn, $sortDir)
            ->paginate(config('admin.pagination_show'))
            ->appends(['sort' => $sortKey, 'dir' => $sortDir]);

        return view('media_platform.podcasts.planning.crud.index', [
            'episodes' => $episodes,
            'sort'     => $sortKey,
            'dir'      => $sortDir,
        ]);
    }

    /**
     * Display a single planning episode.
     * Loads the show and attached guests.
     */
    public function show(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->load(['show', 'guests', 'links']);

        return view('media_platform.podcasts.planning.crud.show', [
            'episode'        => $podcast_episode_planning,
            'manualStatuses' => PodcastEpisodePlanningStatus::manualStatuses(),
        ]);
    }

    /**
     * Show the edit form for a planning episode.
     * Passes the user's shows for the show dropdown, and all manual
     * statuses for the status dropdown.
     */
    public function edit(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $manualStatuses = PodcastEpisodePlanningStatus::manualStatuses();

        return view('media_platform.podcasts.planning.crud.edit', [
            'episode'        => $podcast_episode_planning,
            'manualStatuses' => $manualStatuses,
        ]);
    }

    /**
     * Persist updates to a planning episode.
     * Status changes are restricted to manual statuses only — wizard-managed
     * statuses (new_episode_created, ready_to_record) cannot be set here.
     */
    public function update(
        PodcastEpisodePlanningRequest $request,
        PodcastEpisodePlanning $podcast_episode_planning
    ): RedirectResponse {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->update($request->validated());

        return redirect()
            ->route('podcast_episodes_planning.show', $podcast_episode_planning)
            ->with('success', 'Planning episode updated successfully.');
    }

    /**
     * Show the delete confirmation page for a planning episode.
     */
    public function deleteConfirm(PodcastEpisodePlanning $podcast_episode_planning): View|RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        return view('media_platform.podcasts.planning.crud.delete_confirm', [
            'episode' => $podcast_episode_planning,
        ]);
    }

    /**
     * Hard-delete a planning episode.
     * Planning records are never soft-deleted.
     */
    public function destroy(PodcastEpisodePlanning $podcast_episode_planning): RedirectResponse
    {
        if ($redirect = $this->authorizeOwnership($podcast_episode_planning)) {
            return $redirect;
        }

        $podcast_episode_planning->delete();

        return redirect()
            ->route('podcast_episodes_planning.index')
            ->with('success', 'Planning episode deleted.');
    }
}