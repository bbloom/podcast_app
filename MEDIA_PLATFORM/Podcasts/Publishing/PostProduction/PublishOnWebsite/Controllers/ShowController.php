<?php

// =============================================================================
// ShowController — PublishOnWebsite
//
// RSS PIPELINE REORDER CHANGE:
//   Previously only accepted `ready_to_publish`. Now also accepts
//   `ready_to_publish_website` (the new pipeline entry status set by
//   UploadToStorageController after the MP3 is on S3 and R2).
//   `ready_to_publish` is retained for legacy episodes that entered the
//   pipeline before the reorder was deployed.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/PublishOnWebsite/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\PublishOnWebsite\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class ShowController extends Controller
{
    /**
     * Display the publish confirmation page for the given episode.
     *
     * Accepts both `ready_to_publish_website` (new pipeline) and
     * `ready_to_publish` (legacy pipeline) so that episodes created
     * before and after the reorder both reach this page correctly.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.publish_on_website.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        $acceptableStatuses = [
            PodcastEpisodeStatus::ready_to_publish_website,
            PodcastEpisodeStatus::ready_to_publish,
        ];

        if (! in_array($podcastEpisode->status, $acceptableStatuses)) {
            return redirect()
                ->route('post_production.publish_on_website.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for publishing.');
        }

        return view('media_platform.podcasts.publishing.post_production.publish_on_website.show', [
            'episode' => $podcastEpisode->load('show'),
        ]);
    }
}