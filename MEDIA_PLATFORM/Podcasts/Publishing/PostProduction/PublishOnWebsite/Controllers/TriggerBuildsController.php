<?php

// =============================================================================
// TriggerBuildsController
//
// Handles the "Trigger Static Site Builds" step that follows publishing an
// episode on the website.
//
// Two actions:
//
//   select()  — displays a list of enabled deploy hooks for the episode's show,
//               each with a checkbox. The user selects which builds to trigger.
//               Can be reached from two entry points:
//                 1. After PublishController redirects here (post-publish flow)
//                 2. Directly from the podcast show's show view (manual trigger)
//
//   trigger() — fires the selected deploy hooks via DeployHookTriggerService,
//               collects results, stores them in the session, and redirects
//               to the results page.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;

class TriggerBuildsController extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  select()                                                              │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Display the deploy hook selection page for the given show.
     *
     * Shows all enabled deploy hooks for the show as checkboxes. The user
     * selects which builds to trigger and submits the form.
     *
     * If the show has no enabled deploy hooks, redirects with a notice
     * rather than showing an empty form.
     *
     * Ownership is enforced — the show must belong to the authenticated user.
     *
     * The $context parameter controls breadcrumb and cancel link behaviour:
     *   'publish'  — arrived here after publishing an episode
     *   'show'     — arrived here directly from the podcast show view
     */
    public function select(PodcastShow $podcastShow, string $context = 'show'): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_shows.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Load enabled deploy hooks for this show ───────────────────────────
        $hooks = DeployHook::where('triggerable_type', 'podcast_show')
            ->where('triggerable_id', $podcastShow->id)
            ->where('enabled', true)
            ->orderBy('label')
            ->get();

        if ($hooks->isEmpty()) {
            return redirect()
                ->route('podcast_shows.show', $podcastShow)
                ->with('error', 'No enabled deploy hooks found for "' . $podcastShow->title . '". Add one in Deploy Hooks before triggering a build.');
        }

        return view('media_platform.podcasts.publishing.post_production.publish_on_website.trigger_builds_select', [
            'show'    => $podcastShow,
            'hooks'   => $hooks,
            'context' => $context,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  trigger()                                                             │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Fire the selected deploy hooks and redirect to the results page.
     *
     * Validates that at least one hook was selected. Fires each selected
     * hook via DeployHookTriggerService. Stores results in the session and
     * redirects to the results page.
     *
     * Each hook is fired independently — one failure does not block the others.
     */
    public function trigger(PodcastShow $podcastShow, Request $request, DeployHookTriggerService $service): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_shows.index')
                ->with('error', 'You do not have permission to access that show.');
        }

        // ── Validate at least one hook was selected ───────────────────────────
        $selectedIds = $request->input('hook_ids', []);

        if (empty($selectedIds)) {
            return redirect()
                ->route('post_production.trigger_builds.select', $podcastShow)
                ->with('error', 'Please select at least one deploy hook to trigger.');
        }

        // ── Load the selected hooks ───────────────────────────────────────────
        // Only fire hooks that belong to this show and are enabled — never
        // trust the submitted IDs blindly.
        $hooks = DeployHook::where('triggerable_type', 'podcast_show')
            ->where('triggerable_id', $podcastShow->id)
            ->where('enabled', true)
            ->whereIn('id', $selectedIds)
            ->get();

        // ── Fire each selected hook ───────────────────────────────────────────
        $results = [];

        foreach ($hooks as $hook) {
            $results[] = $service->trigger($hook);
        }

        // ── Store results in session and redirect to results page ─────────────
        session(['trigger_builds.results' => $results]);

        return redirect()->route('post_production.trigger_builds.results', $podcastShow);
    }
}