<?php

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;

class DoneController extends Controller
{
    /**
     * Display the "RSS Feed Live — what next?" page.
     *
     * Shown after GenerateRssFeed\Step5Controller::store() succeeds.
     * All work is done — RSS feed promoted to live S3 and R2, staging and
     * local files deleted, session cleared, status advanced to ready_to_publish.
     * The user chooses to continue to Publish on Website or return to the
     * Post-Production Dashboard.
     */
    public function __invoke(PodcastEpisode $podcastEpisode): View|RedirectResponse
    {
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        return view(
            'media_platform.podcasts.publishing.post_production.generate_rss_feed.done',
            ['episode' => $podcastEpisode->load('show')]
        );
    }
}