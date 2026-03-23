<?php

namespace MediaPlatform\Digest\Processing\Youtube\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Contracts\ContentProcessorInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Models\ListSourceTracking;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * YoutubeContentProcessor
 *
 * !! READ BEFORE MODIFYING !!
 * ════════════════════════════════════════════════════════════════════════════
 * CORE ASSUMPTION: YouTube's playlistItems API returns videos newest-first.
 * This processor relies on that sort order to stop processing as soon as it
 * reaches a video it has already seen.
 *
 * See app/Processing/README_PROCESSING_ASSUMPTIONS.md for the full design
 * rationale, bookmark strategy, and edge case documentation.
 *
 * PROCESSING FLOW
 * ───────────────
 * process()
 *   └── Loads channel, tracking, and bookmark.
 *   └── Determines first run vs regular run based on bookmark existence.
 *   └── Delegates to firstRunProcessing() or regularRunProcessing().
 *   └── Records tracking success.
 *
 * firstRunProcessing()
 *   └── Fetches videos from the YouTube API.
 *   └── Walks videos newest-first.
 *   └── Skips videos older than the lookback window.
 *   └── Processes everything within the lookback window.
 *   └── INSERTS a new bookmark pointing to the newest processed video.
 *   └── No bookmark exists yet — this is an INSERT, not a rotation.
 *
 * regularRunProcessing()
 *   └── Fetches videos from the YouTube API.
 *   └── Walks videos newest-first.
 *   └── STOPS at the bookmarked video URL (normal case).
 *   └── STOPS when a video's published_at is older than the bookmark's
 *       processed_at (bookmark video deleted from YouTube — fallback stop).
 *   └── Processes everything before the stop point.
 *   └── If anything was processed: DELETE old bookmark, INSERT new bookmark
 *       pointing to the newest video processed this run (rotation).
 *   └── If nothing was processed: bookmark is left completely unchanged.
 *
 * DESCRIPTION CLEANING
 * ────────────────────
 * cleanDescription() scans the raw YouTube description top-to-bottom and
 * stops at the first line that signals boilerplate (bare URL, chapter
 * timestamp, known section header). Subscribe-lines are skipped silently
 * before any real content is collected; once content has started they act
 * as a stop signal. Returns "No description provided" when nothing useful
 * is found.
 *
 * The cleaned description is used in all three processing modes:
 *   - description mode  → stored directly as summary_html
 *   - summary mode      → fallback when transcript is unavailable
 *   - search mode       → fallback at Tier 3 when transcript is unavailable
 * ════════════════════════════════════════════════════════════════════════════
 */
class YoutubeContentProcessor implements ContentProcessorInterface
{
    private const USE_CASE_SLUG = 'digest-processing';

    /**
     * Section headers that mark the start of boilerplate.
     * Matched case-insensitively against trimmed lines (trailing :—- stripped).
     */
    private const STOP_HEADERS = [
        'CHAPTERS', 'CHAPTER', 'TIMESTAMPS', 'TIMESTAMP',
        'LINKS', 'FOLLOW', 'CONNECT', 'SUPPORT', 'SPONSORS', 'SPONSORED',
        'AFFILIATE', 'MERCH', 'SOCIALS', 'PATREON',
    ];

    /**
     * Substrings that identify a subscribe-line.
     * Matched case-insensitively against the full line.
     */
    private const SUBSCRIBE_PATTERNS = [
        'subscribe', 'hit the bell', 'click the bell', 'turn on notification',
    ];

    public function __construct(
        private LlmService $llm,
    ) {}

    // =========================================================================
    // Entry Point
    // =========================================================================

    /**
     * Process a single list_source row for a YouTube channel.
     *
     * Determines whether this is a first run (no bookmark exists) or a regular
     * run (bookmark exists), then delegates accordingly. Feed fetching happens
     * inside each run method so the two paths are completely self-contained.
     */
    public function process(object $listSource): array
    {
        $stats = ['fetched' => 0, 'processed' => 0, 'skipped' => 0, 'errors' => 0];

        // ── Load the channel record ───────────────────────────────────────────
        $channel = YoutubeChannel::find($listSource->sourceable_id);

        if (! $channel) {
            Log::error("YoutubeContentProcessor: Channel not found for list_source {$listSource->id}.");
            return $stats;
        }

        // ── Load or create tracking record ────────────────────────────────────
        $tracking = ListSourceTracking::findOrCreateFor($listSource->id);

        // ── Resolve the list owner's user_id ─────────────────────────────────
        // Needed by both run methods for inserting summaries and bookmarks.
        $userId = DB::table('list_sources')
            ->join('lists', 'lists.id', '=', 'list_sources.list_id')
            ->where('list_sources.id', $listSource->id)
            ->value('lists.user_id');

        // ── Determine first run vs regular run ────────────────────────────────
        // A bookmark's existence — not last_fetched_at — determines the run type.
        // This is intentional: last_fetched_at can be set even if the bookmark
        // was never written (e.g. a run that processed nothing). The bookmark
        // is the authoritative signal.
        $bookmark = ContentAlreadyProcessed::findBookmark($listSource->id);

        if ($bookmark === null) {
            // ── FIRST RUN ─────────────────────────────────────────────────────
            Log::info("YoutubeContentProcessor: First run detected for list_source {$listSource->id}.");
            $stats = $this->firstRunProcessing($listSource, $channel, $userId, $stats);
        } else {
            // ── REGULAR RUN ───────────────────────────────────────────────────
            Log::info("YoutubeContentProcessor: Regular run detected for list_source {$listSource->id}. Bookmark: {$bookmark->source_url}");
            $stats = $this->regularRunProcessing($listSource, $channel, $userId, $bookmark, $stats);
        }

        // ── Record successful run in tracking ─────────────────────────────────
        $tracking->recordSuccess();

        return $stats;
    }

    // =========================================================================
    // First Run Processing
    // =========================================================================

    /**
     * Handle the very first processing run for a list_source.
     *
     * On a first run, no bookmark exists. We apply a lookback window to avoid
     * flooding the summaries table with back-catalogue content. Only videos
     * published within the lookback window are processed.
     *
     * After processing, if at least one video was processed, we INSERT a new
     * bookmark pointing to the newest video processed. This is an INSERT, not
     * a rotation — there is no existing record to delete.
     *
     * If nothing was processed (all videos were older than the lookback window),
     * no bookmark is inserted. The next run will also be treated as a first run.
     */
    private function firstRunProcessing(
        object         $listSource,
        YoutubeChannel $channel,
        int            $userId,
        array          $stats,
    ): array {
        // ── Fetch videos from the YouTube API ─────────────────────────────────
        $videos = $this->fetchPlaylistItems($channel);

        if ($videos === null) {
            $this->recordFetchFailure($listSource, $channel->title);
            $stats['errors']++;
            return $stats;
        }

        $stats['fetched'] = count($videos);
        $lookbackDays     = config('processing.first_run_lookback_days', 2);
        $newestProcessedUrl = null;

        // ── Walk videos newest-first ──────────────────────────────────────────
        foreach ($videos as $video) {
            $videoId = $video['snippet']['resourceId']['videoId'] ?? null;

            if (! $videoId) {
                continue;
            }

            $sourceUrl   = "https://www.youtube.com/watch?v={$videoId}";
            $rawDate     = $video['contentDetails']['videoPublishedAt']
                        ?? $video['snippet']['publishedAt']
                        ?? null;
            $publishedAt = $rawDate ? Carbon::parse($rawDate) : null;

            // ── Lookback window check ─────────────────────────────────────────
            // Since items are newest-first, once we hit one older than the
            // lookback window, all subsequent items are also older — we could
            // break here for efficiency. However, we continue with 'continue'
            // rather than 'break' to keep the skipped count accurate for the
            // full feed. Both approaches are correct.

            // Items with no published_at cannot be age-checked — process them.
            if ($publishedAt && $publishedAt->lt(now()->subDays($lookbackDays))) {
                $stats['skipped']++;
                continue;
            }

            // ── Process this video ────────────────────────────────────────────
            try {
                $this->processVideo(
                    listSource:  $listSource,
                    video:       $video,
                    sourceUrl:   $sourceUrl,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;

                // Track the first (newest) URL we process — this becomes the bookmark.
                // The ??= operator ensures we only capture the very first one.
                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("YoutubeContentProcessor: Failed to process video {$sourceUrl}", [
                    'list_source_id' => $listSource->id,
                    'error'          => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // ── Insert bookmark if anything was processed ─────────────────────────
        // This is an INSERT, not a rotation. There is no existing bookmark to delete.
        // If nothing was processed, we intentionally leave no bookmark so the next
        // run is also treated as a first run.
        if ($newestProcessedUrl !== null) {
            ContentAlreadyProcessed::rotateBookmark(
                listSourceId: $listSource->id,
                userId:       $userId,
                sourceUrl:    $newestProcessedUrl,
            );
            Log::info("YoutubeContentProcessor: First run complete. Bookmark inserted: {$newestProcessedUrl}");
        } else {
            Log::info("YoutubeContentProcessor: First run complete. No items processed — no bookmark inserted.");
        }

        return $stats;
    }

    // =========================================================================
    // Regular Run Processing
    // =========================================================================

    /**
     * Handle a regular (non-first) processing run for a list_source.
     *
     * On a regular run, a bookmark exists from a previous run. We walk the
     * feed newest-first and stop as soon as we reach the bookmarked item.
     *
     * TWO STOP CONDITIONS:
     *
     * 1. NORMAL STOP: The current item's URL matches the bookmark URL.
     *    This is the expected case — we've reached the last item we processed.
     *
     * 2. FALLBACK STOP: The current item's published_at is older than the
     *    bookmark's processed_at timestamp. This handles the case where the
     *    bookmarked item has been deleted from YouTube (or removed from the
     *    playlist). We can't find the exact URL, but we know we've passed the
     *    point where it would have appeared.
     *
     * After processing, if at least one item was processed, we rotate the
     * bookmark: DELETE the old record, INSERT a new one pointing to the newest
     * item processed this run.
     *
     * If nothing was processed (the very first item in the feed was already the
     * bookmark), the bookmark is left completely unchanged.
     */
    private function regularRunProcessing(
        object                  $listSource,
        YoutubeChannel          $channel,
        int                     $userId,
        ContentAlreadyProcessed $bookmark,
        array                   $stats,
    ): array {
        // ── Fetch videos from the YouTube API ─────────────────────────────────
        $videos = $this->fetchPlaylistItems($channel);

        if ($videos === null) {
            $this->recordFetchFailure($listSource, $channel->title);
            $stats['errors']++;
            return $stats;
        }

        $stats['fetched']   = count($videos);
        $newestProcessedUrl = null;

        // ── Walk videos newest-first ──────────────────────────────────────────
        foreach ($videos as $video) {
            $videoId = $video['snippet']['resourceId']['videoId'] ?? null;

            if (! $videoId) {
                continue;
            }

            $sourceUrl   = "https://www.youtube.com/watch?v={$videoId}";
            $rawDate     = $video['contentDetails']['videoPublishedAt']
                        ?? $video['snippet']['publishedAt']
                        ?? null;
            $publishedAt = $rawDate ? Carbon::parse($rawDate) : null;

            // ── Stop condition 1: URL matches bookmark ────────────────────────
            if ($sourceUrl === $bookmark->source_url) {
                break;
            }

            // ── Stop condition 2: item is older than the bookmark ─────────────
            if ($publishedAt && $publishedAt->lt(Carbon::parse($bookmark->processed_at))) {
                break;
            }

            // ── Process this video ────────────────────────────────────────────
            try {
                $this->processVideo(
                    listSource:  $listSource,
                    video:       $video,
                    sourceUrl:   $sourceUrl,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;
                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("YoutubeContentProcessor: Failed to process video {$sourceUrl}", [
                    'list_source_id' => $listSource->id,
                    'error'          => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // ── Rotate bookmark if anything was processed ─────────────────────────
        // If nothing was processed, the bookmark stays exactly as it was.
        if ($newestProcessedUrl !== null) {
            ContentAlreadyProcessed::rotateBookmark(
                listSourceId: $listSource->id,
                userId:       $userId,
                sourceUrl:    $newestProcessedUrl,
            );
            Log::info("YoutubeContentProcessor: Regular run complete. Bookmark rotated to: {$newestProcessedUrl}");
        } else {
            Log::info("YoutubeContentProcessor: Regular run complete. Nothing new — bookmark unchanged.");
        }

        return $stats;
    }

    // =========================================================================
    // Video Processing
    // =========================================================================

    /**
     * Process a single video: build summary HTML based on the processing mode
     * and insert a row into the summaries table.
     *
     * Called by both firstRunProcessing() and regularRunProcessing().
     */
    private function processVideo(
        object $listSource,
        array  $video,
        string $sourceUrl,
        Carbon $publishedAt,
        int    $userId,
    ): void {
        $snippet     = $video['snippet'];
        $title       = $snippet['title'] ?? 'Untitled';
        $description = $snippet['description'] ?? '';
        $videoId     = $snippet['resourceId']['videoId'];
        $cleaned     = $this->cleanDescription($description);

        [$summaryHtml, $isRelevant] = match ($listSource->processing_mode) {
            'description' => [
                '<p>' . nl2br(e($cleaned)) . '</p>',
                true,
            ],
            'summary' => [
                $this->summarise($videoId, $title, $cleaned),
                true,
            ],
            'search' => array_values(
                $this->searchAndSummarise($videoId, $title, $description, $cleaned, $listSource->search_terms)
            ),
            default => [null, true],
        };

        DB::table('summaries')->insert([
            'user_id'               => $userId,
            'list_source_id'        => $listSource->id,
            'source_url'            => $sourceUrl,
            'source_title'          => $title,
            'source_description'    => $description,
            'source_published_at'   => $publishedAt,
            'processing_mode'       => $listSource->processing_mode,
            'summary_html'          => $summaryHtml,
            'is_relevant'           => $isRelevant,
            'included_in_digest'    => false,
            'included_in_digest_at' => null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    // =========================================================================
    // Description Cleaning
    // =========================================================================

    /**
     * Extract the useful body text from a YouTube description.
     *
     * Strategy: scan lines top-to-bottom and stop at the first line that
     * signals the start of boilerplate (bare URL, chapter timestamp, known
     * section header). Subscribe-lines are skipped silently while no real
     * content has yet been collected; once content collection has started,
     * a subscribe-line acts as a stop signal.
     *
     * Returns "No description provided" when nothing useful is found.
     *
     * Visibility: public intentionally — allows direct unit testing without
     * going through the full processor pipeline, and leaves the door open
     * for future use (e.g. a shared description-cleaning service).
     */
    public function cleanDescription(string $description): string
    {
        $lines        = explode("\n", $description);
        $body         = [];
        $foundContent = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // ── Blank line — carry on, don't stop ────────────────────────────
            if ($trimmed === '') {
                // Only buffer the blank if we already have content — avoids
                // leading blank lines in the output.
                if ($foundContent) {
                    $body[] = '';
                }
                continue;
            }

            // ── Bare URL — stop ───────────────────────────────────────────────
            if (preg_match('#^https?://#i', $trimmed)) {
                break;
            }

            // ── Chapter timestamp — stop (e.g. "0:00", "1:23:45 Intro") ─────
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?(\s|$)/', $trimmed)) {
                break;
            }

            // ── Known section header — stop ───────────────────────────────────
            // Strip trailing punctuation like ":" or "—" before comparing.
            $headerCandidate = strtoupper(rtrim($trimmed, ' :—-'));
            if (in_array($headerCandidate, self::STOP_HEADERS, true)) {
                break;
            }

            // ── Subscribe line ────────────────────────────────────────────────
            $lower           = strtolower($trimmed);
            $isSubscribeLine = false;
            foreach (self::SUBSCRIBE_PATTERNS as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $isSubscribeLine = true;
                    break;
                }
            }

            if ($isSubscribeLine) {
                if ($foundContent) {
                    // Subscribe line after real content — treat as stop signal.
                    break;
                }
                // Subscribe line before any content — skip silently.
                continue;
            }

            // ── Real content line ─────────────────────────────────────────────
            $body[]       = $trimmed;
            $foundContent = true;
        }

        // Drop trailing blank lines left in the buffer.
        while (! empty($body) && end($body) === '') {
            array_pop($body);
        }

        $result = trim(implode("\n", $body));

        return $result !== '' ? $result : 'No description provided';
    }

    // =========================================================================
    // LLM: Summary
    // =========================================================================

    /**
     * Fetch the video transcript and summarise it via the configured LLM.
     *
     * Falls back to the cleaned description when the transcript is unavailable.
     * Only returns the "unavailable" message when both transcript and description
     * are absent.
     *
     * @param string $description Pre-cleaned description from cleanDescription().
     */
    private function summarise(string $videoId, string $title, string $description): string
    {
        $transcript = $this->fetchTranscript($videoId);

        if ($transcript === null) {
            if ($description !== 'No description provided') {
                return '<p>' . nl2br(e($description)) . '</p>';
            }
            return '<p><em>Transcript unavailable for this video.</em></p>';
        }

        $prompt = str_replace('{title}', $title, config('prompts.youtube_summary'))
            . $transcript;

        try {
            return $this->llm->generateContent(self::USE_CASE_SLUG, $prompt);

        } catch (LlmAuthenticationException $e) {
            Log::error("LLM auth error for video {$videoId}", ['error' => $e->getMessage()]);

            AdminAlert::raiseIfNew(
                tier: 3,
                category: 'gemini',
                title: "{$e->providerSlug} API key invalid",
                message: "Authentication failed with {$e->providerSlug} provider. Check the API key in .env.",
            );

            return '<p><em>Summary generation failed — authentication error.</em></p>';

        } catch (LlmRateLimitException $e) {
            Log::warning("LLM rate limited for video {$videoId}", ['error' => $e->getMessage()]);
            return '<p><em>Summary generation deferred — rate limited. Will retry on next run.</em></p>';

        } catch (LlmException $e) {
            Log::error("LLM error for video {$videoId}", ['error' => $e->getMessage()]);
            return '<p><em>Summary generation failed. Will retry on next run.</em></p>';
        }
    }

    // =========================================================================
    // LLM: Search + Summarise
    // =========================================================================

    /**
     * Tiered search: title → description → LLM semantic relevance on transcript.
     *
     * Tier 1 & 2 are free (string matching).
     * Tier 3 fires an LLM call — only runs if both cheaper checks fail.
     *
     * When the transcript is unavailable at Tier 3, falls back to the cleaned
     * description rather than returning not-relevant.
     *
     * @param string $description  Raw description (used for Tier 2 matching).
     * @param string $cleaned      Pre-cleaned description (used as fallback content).
     */
    private function searchAndSummarise(
        string  $videoId,
        string  $title,
        string  $description,
        string  $cleaned,
        ?string $searchTerms,
    ): array {
        if (empty($searchTerms)) {
            return ['summary' => null, 'is_relevant' => false];
        }

        $terms = array_map('trim', explode(',', $searchTerms));

        // Tier 1: title match (free)
        if ($this->matchesTerms($title, $terms)) {
            return ['summary' => $this->summarise($videoId, $title, $cleaned), 'is_relevant' => true];
        }

        // Tier 2: description match (free)
        if ($this->matchesTerms($description, $terms)) {
            return ['summary' => $this->summarise($videoId, $title, $cleaned), 'is_relevant' => true];
        }

        // Tier 3: LLM semantic check on transcript (costs an LLM call)
        $transcript = $this->fetchTranscript($videoId);

        if ($transcript === null) {
            // No transcript — fall back to the cleaned description if available.
            if ($cleaned !== 'No description provided') {
                return ['summary' => '<p>' . nl2br(e($cleaned)) . '</p>', 'is_relevant' => true];
            }
            return ['summary' => null, 'is_relevant' => false];
        }

        $prompt = str_replace(
            ['{search_terms}', '{title}'],
            [$searchTerms,     $title],
            config('prompts.youtube_search')
        ) . $transcript;

        try {
            $result = trim($this->llm->generateContent(self::USE_CASE_SLUG, $prompt));

            // "NOT_RELEVANT" is the sentinel string the prompt instructs the model
            // to return when the content does not match the search terms.
            // See config/prompts.php for the coupling note.
            if ($result === 'NOT_RELEVANT') {
                return ['summary' => null, 'is_relevant' => false];
            }

            return ['summary' => $result, 'is_relevant' => true];

        } catch (LlmException $e) {
            Log::error("LLM search+summarise failed for video {$videoId}", [
                'error' => $e->getMessage(),
            ]);
            return ['summary' => null, 'is_relevant' => false];
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Case-insensitive check: does any search term appear in the given text?
     */
    private function matchesTerms(string $text, array $terms): bool
    {
        $lower = strtolower($text);

        foreach ($terms as $term) {
            if (str_contains($lower, strtolower($term))) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // YouTube API
    // =========================================================================

    /**
     * Call the YouTube Data API playlistItems.list endpoint to get the channel's
     * uploaded videos. Returns null on any failure, empty array when no videos.
     *
     * The uploads playlist ID is derived from the channel ID by replacing the
     * first two characters ("UC") with "UU".
     */
    private function fetchPlaylistItems(YoutubeChannel $channel): ?array
    {
        $uploadsPlaylistId = 'UU' . substr($channel->channel_id, 2);

        try {
            $response = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/playlistItems', [
                'part'       => 'snippet,contentDetails',
                'playlistId' => $uploadsPlaylistId,
                'maxResults' => 50,
                'key'        => config('youtube.api_key'),
            ]);

            if ($response->status() === 403) {
                Log::error('YouTube API 403 — likely quota exceeded or key invalid.');

                AdminAlert::raiseIfNew(
                    tier: 2,
                    category: 'youtube',
                    title: 'YouTube API quota exceeded or key invalid',
                    message: 'Received HTTP 403 from the YouTube Data API. Check your API key and daily quota in the Google Cloud Console.',
                );

                return null;
            }

            if ($response->failed()) {
                Log::error("YouTube API error: HTTP {$response->status()}");
                return null;
            }

            return $response->json('items', []);

        } catch (\Throwable $e) {
            Log::error("YouTube API request failed: {$e->getMessage()}");
            return null;
        }
    }

    // =========================================================================
    // Transcript
    // =========================================================================

    /**
     * Run the Python transcript script and return plain text, or null on failure.
     *
     * The script returns JSON: {"transcript": "..."} or {"transcript": "ERROR: ..."}
     * when captions are unavailable.
     */
    private function fetchTranscript(string $videoId): ?string
    {
        $scriptPath = config('processing.transcript_script_path', base_path('scripts/get_transcript.py'));

        if (! file_exists($scriptPath)) {
            Log::error("YoutubeContentProcessor: Transcript script not found at {$scriptPath}.");
            return null;
        }

        $result = Process::timeout(30)->run("/usr/bin/python3 {$scriptPath} {$videoId}");

        if (! $result->successful()) {
            Log::warning("YoutubeContentProcessor: Transcript script failed for video {$videoId}.");
            return null;
        }

        $data       = json_decode($result->output(), true);
        $transcript = $data['transcript'] ?? null;

        if (! $transcript || str_starts_with($transcript, 'ERROR:')) {
            return null;
        }

        return $transcript;
    }

    // =========================================================================
    // Feed Fetch Failure Handler
    // =========================================================================

    /**
     * Record a feed fetch failure on the tracking record and optionally
     * suspend the list_source and raise an admin alert if the failure
     * threshold has been reached.
     */
    private function recordFetchFailure(object $listSource, string $channelTitle): void
    {
        $tracking = ListSourceTracking::findOrCreateFor($listSource->id);
        $tracking->recordFailure('YouTube API error fetching playlist items.');

        if ($tracking->shouldSuspend()) {
            DB::table('list_sources')
                ->where('id', $listSource->id)
                ->update(['suspended' => true]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'youtube',
                title: "Channel '{$channelTitle}' suspended",
                message: "Channel '{$channelTitle}' has failed {$tracking->consecutive_failures} consecutive times and has been auto-suspended from list_source {$listSource->id}.",
            );
        }
    }
}