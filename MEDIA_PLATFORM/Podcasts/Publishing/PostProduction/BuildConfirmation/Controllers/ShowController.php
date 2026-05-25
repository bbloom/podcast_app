<?php

// =============================================================================
// ShowController — BuildConfirmation
//
// Displays the build confirmation page for an episode that is waiting for its
// Cloudflare Pages static site build to complete.
//
// If the episode's show has an enabled Cloudflare Pages deploy hook with a
// recent build ID, the page auto-polls the build status endpoint and advances
// automatically when the build succeeds.
//
// If no Cloudflare Pages hook is found (e.g. the show uses Netlify or Vercel,
// or has no deploy hooks), a manual confirmation option is shown instead —
// the user checks their hosting dashboard and confirms manually.
//
// Path: MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/BuildConfirmation/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\BuildConfirmation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class ShowController extends Controller
{
    /**
     * Display the build confirmation page for the given episode.
     *
     * Resolves the most recently triggered Cloudflare Pages hook for the
     * episode's show and passes it to the view. If no hook is found, the
     * view falls back to a manual confirmation flow.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('podcast_episodes.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        // This page is only valid while the episode is in `build_triggered`.
        // Any other status means the episode is either behind or ahead in the
        // pipeline — redirect to the appropriate step.
        if ($podcastEpisode->status !== PodcastEpisodeStatus::build_triggered) {
            return redirect()
                ->route($podcastEpisode->status->postProductionShowRoute(), $podcastEpisode)
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not awaiting a build confirmation.');
        }

        // ── Resolve the Cloudflare Pages hook for this show ───────────────────
        // Find the most recently triggered enabled Cloudflare Pages hook.
        // A build ID must be present — if the hook has never been triggered,
        // there is nothing to poll.
        $cloudflareHook = DeployHook::where('triggerable_type', 'podcast_show')
            ->where('triggerable_id', $podcastEpisode->show->id)
            ->where('provider', DeployHookProvider::cloudflare_pages)
            ->where('enabled', true)
            ->whereNotNull('last_build_id')
            ->orderByDesc('last_triggered_at')
            ->first();

        return view(
            'media_platform.podcasts.publishing.post_production.build_confirmation.show',
            [
                'episode'        => $podcastEpisode,
                'cloudflareHook' => $cloudflareHook,
            ]
        );
    }
}