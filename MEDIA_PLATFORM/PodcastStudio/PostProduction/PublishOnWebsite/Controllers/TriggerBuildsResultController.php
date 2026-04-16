<?php

// =============================================================================
// TriggerBuildsResultController
//
// Displays the results of a "Trigger Static Site Builds" action.
//
// Results are passed via the session — stored by TriggerBuildsController
// after firing the selected hooks. The session is cleared after display
// so the results page cannot be revisited stale.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;

class TriggerBuildsResultController extends Controller
{
    /**
     * Display the trigger results page.
     *
     * Reads results from the session. If no results are found (e.g. the user
     * revisits the page after the session has cleared), redirects back to the
     * show page with a notice.
     *
     * Clears the session key after reading so stale results are never shown.
     */
    public function __invoke(PodcastShow $podcastShow): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_shows.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Read and clear results from session ───────────────────────────────
        $results = session()->pull('trigger_builds.results');

        if (empty($results)) {
            return redirect()
                ->route('podcast_shows.show', $podcastShow)
                ->with('error', 'No trigger results found. Please trigger a build from the show page.');
        }

        return view('media_platform.podcast_studio.post_production.publish_on_website.trigger_builds_results', [
            'show'    => $podcastShow,
            'results' => $results,
        ]);
    }
}