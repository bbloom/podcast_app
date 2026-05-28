<?php

// =============================================================================
// Step2Controller
//
// Step 2 of the Generate RSS Feed wizard — pre-generation validation.
//
// show() runs RssFeedValidatorService against the episode and its show.
//   - All fields present and non-empty → redirect to Step 3.
//   - Any field missing or empty      → render validation results page.
//   - R2 download failed              → render inline manual confirmation form.
//
// store() handles the R2 manual confirmation form submission.
//   - Saves itunes_enclosure_length and itunes_duration to the episode.
//   - Sets a session flag so the validator skips R2 on the next pass.
//   - Redirects back to show() which re-runs the validator.
//
// Path: MEDIA_PLATFORM/Podcasts/PostProduction/GenerateRssFeed/Controllers/
// =============================================================================

namespace MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\PostProduction\GenerateRssFeed\Services\RssFeedValidatorService;

class Step2Controller extends Controller
{
    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  show()                                                                │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Run pre-generation validation and render the results.
     *
     * If validation passes completely, the user is forwarded directly to Step 3
     * without seeing this page at all. The page only renders when there is
     * something to show — failures, warnings, or an R2 download problem.
     */
    public function show(PodcastEpisode $podcastEpisode, RssFeedValidatorService $validator): View|RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for RSS feed generation.');
        }

        // ── Session guard — must have come through Step 1 ─────────────────────
        $sessionEpisodeId = session('wizard.generate_rss_feed.podcast_episode_id');

        if ($sessionEpisodeId !== $podcastEpisode->id) {
            return redirect()
                ->route('post_production.generate_rss_feed.step1', $podcastEpisode)
                ->with('error', 'Please start from Step 1.');
        }

        // ── Load the show ─────────────────────────────────────────────────────
        $podcastEpisode->load('show');
        $show = $podcastEpisode->show;

        // ── Run validation ────────────────────────────────────────────────────
        $result = $validator->validate($podcastEpisode, $show);

        // ── All clear — proceed to Step 3 ─────────────────────────────────────
        if ($result->ok() && ! $result->r2DownloadFailed()) {
            return redirect()->route('post_production.generate_rss_feed.step3', $podcastEpisode);
        }

        // ── Render validation results ─────────────────────────────────────────
        return view('media_platform.podcasts.publishing.post_production.generate_rss_feed.step2', [
            'episode'         => $podcastEpisode,
            'result'          => $result,
        ]);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  store()                                                               │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Handle the R2 manual confirmation form.
     *
     * When R2 is unreachable, Step 2 surfaces inline input fields for
     * itunes_enclosure_length and itunes_duration. The user reviews the
     * pre-filled values, corrects them if needed, and submits this form.
     *
     * On success:
     *   - Saves the two values to the episode record.
     *   - Sets a session flag so the validator skips R2 on the next pass.
     *   - Redirects to show() which re-runs the full validator.
     */
    public function store(PodcastEpisode $podcastEpisode, Request $request): RedirectResponse
    {
        // ── Ownership check ───────────────────────────────────────────────────
        if ($podcastEpisode->user_id !== auth()->id()) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'You do not have permission to access that episode.');
        }

        // ── Status guard ──────────────────────────────────────────────────────
        if ($podcastEpisode->status !== PodcastEpisodeStatus::ready_to_generate_rss_feed) {
            return redirect()
                ->route('post_production.generate_rss_feed.index')
                ->with('error', 'Episode "' . $podcastEpisode->title . '" is not in the expected status for RSS feed generation.');
        }

        // ── Validate the submitted values ─────────────────────────────────────
        $request->validate([
            'itunes_enclosure_length' => ['required', 'numeric', 'min:1'],
            'itunes_duration'         => ['required', 'string', 'max:20'],
        ]);

        // ── Save to episode ───────────────────────────────────────────────────
        $podcastEpisode->update([
            'itunes_enclosure_length' => $request->input('itunes_enclosure_length'),
            'itunes_duration'         => $request->input('itunes_duration'),
        ]);

        // ── Set session flag — R2 check skipped on next validation pass ───────
        session(['wizard.generate_rss_feed.enclosure_manually_verified_' . $podcastEpisode->id => true]);

        // ── Re-run validation via show() ──────────────────────────────────────
        return redirect()
            ->route('post_production.generate_rss_feed.step2', $podcastEpisode)
            ->with('success', 'Enclosure values saved. Re-running validation...');
    }
}