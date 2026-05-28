<?php

// =============================================================================
// TriggerBuildsController
//
// Handles the "Trigger Static Site Builds" step.
//
// RSS PIPELINE REORDER CHANGES (trigger() only):
//   After firing hooks, trigger() checks the session for a pending episode ID
//   (stored by PublishController or PrepareTriggerBuildsController). If found:
//     - Advances the episode to `build_triggered`
//     - Redirects to BuildConfirmation
//   If not found (manual trigger from show page), falls back to the existing
//   results page flow.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
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
     * Unchanged from the original implementation.
     */
    public function select(PodcastShow $podcastShow, string $context = 'show'): View|RedirectResponse
    {
        if ($podcastShow->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_shows.index')
                ->with('error', 'You do not have permission to access that show.');
        }

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

        // Detect pipeline context — if the session has a pending episode ID the
        // user arrived from PublishController or PrepareTriggerBuildsController.
        // Override the default 'show' context so the breadcrumb shows
        // "Publish on Website" instead of the show page link.
        if (session()->has('build_confirmation.pending_episode_id')) {
            $context = 'publish';
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
     * Fire the selected deploy hooks.
     *
     * Pipeline context (session has `build_confirmation.pending_episode_id`):
     *   Advances the episode to `build_triggered` and redirects to
     *   BuildConfirmation so the user waits for the Cloudflare build to
     *   complete before generating the RSS feed.
     *
     * Manual context (no session key):
     *   Stores results in session and redirects to the results page.
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

        // ── Load and fire selected hooks ──────────────────────────────────────
        $hooks = DeployHook::where('triggerable_type', 'podcast_show')
            ->where('triggerable_id', $podcastShow->id)
            ->where('enabled', true)
            ->whereIn('id', $selectedIds)
            ->get();

        $results = [];

        foreach ($hooks as $hook) {
            $results[] = $service->trigger($hook);
        }

        // ── Pipeline context — advance episode and go to BuildConfirmation ─────
        // The session key is set by PublishController or
        // PrepareTriggerBuildsController. pull() reads and removes it atomically.
        $pendingEpisodeId = session()->pull('build_confirmation.pending_episode_id');

        if ($pendingEpisodeId) {
            $episode = PodcastEpisode::find($pendingEpisodeId);

            if ($episode &&
                $episode->user_id === auth()->id() &&
                $episode->status === PodcastEpisodeStatus::website_published) {

                $episode->update(['status' => PodcastEpisodeStatus::build_triggered]);

                return redirect()->route('post_production.build_confirmation.show', $episode);
            }
        }

        // ── Manual context — show trigger results ─────────────────────────────
        session(['trigger_builds.results' => $results]);

        return redirect()->route('post_production.trigger_builds.results', $podcastShow);
    }
}