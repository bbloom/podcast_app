<?php

namespace MediaPlatform\Digest\Processing\TextBasedRss\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Contracts\ContentProcessorInterface;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Models\ListSourceTracking;
use MediaPlatform\Digest\Processing\Services\ArticleExtractorService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TextBasedRssContentProcessor
 *
 * !! READ BEFORE MODIFYING !!
 * ════════════════════════════════════════════════════════════════════════════
 * CORE ASSUMPTION: RSS and Atom feeds list items newest-first.
 * This processor relies on that sort order to stop processing as soon as it
 * reaches an item it has already seen.
 *
 * See app/Processing/README_PROCESSING_ASSUMPTIONS.md for the full design
 * rationale, bookmark strategy, and edge case documentation.
 *
 * PROCESSING FLOW
 * ───────────────
 * process()
 *   └── Loads feed, tracking, and bookmark.
 *   └── Determines first run vs regular run based on bookmark existence.
 *   └── Delegates to firstRunProcessing() or regularRunProcessing().
 *   └── Records tracking success.
 *
 * firstRunProcessing()
 *   └── Fetches and parses the RSS/Atom feed.
 *   └── Walks items newest-first.
 *   └── Skips items older than the lookback window.
 *   └── Processes everything within the lookback window.
 *   └── INSERTS a new bookmark pointing to the newest processed item.
 *   └── No bookmark exists yet — this is an INSERT, not a rotation.
 *
 * regularRunProcessing()
 *   └── Fetches and parses the RSS/Atom feed.
 *   └── Walks items newest-first.
 *   └── STOPS at the bookmarked item URL (normal case).
 *   └── STOPS when an item's published_at is older than the bookmark's
 *       processed_at (bookmarked item deleted from feed — fallback stop).
 *   └── Processes everything before the stop point.
 *   └── If anything was processed: DELETE old bookmark, INSERT new bookmark
 *       pointing to the newest item processed this run (rotation).
 *   └── If nothing was processed: bookmark is left completely unchanged.
 * ════════════════════════════════════════════════════════════════════════════
 */
class TextBasedRssContentProcessor implements ContentProcessorInterface
{
    private const USE_CASE_SLUG = 'digest-processing';

    public function __construct(
        private LlmService $llm,
        private ArticleExtractorService $extractor,
    ) {}

    // =========================================================================
    // Entry Point
    // =========================================================================

    /**
     * Process a single list_source row for a text-based RSS feed.
     *
     * Determines whether this is a first run (no bookmark exists) or a regular
     * run (bookmark exists), then delegates accordingly. Feed fetching happens
     * inside each run method so the two paths are completely self-contained.
     */
    public function process(object $listSource): array
    {
        $stats = ['fetched' => 0, 'processed' => 0, 'skipped' => 0, 'errors' => 0];

        // ── Load the feed record ──────────────────────────────────────────────
        $feed = TextBasedRssFeed::find($listSource->sourceable_id);

        if (! $feed) {
            Log::error("TextBasedRssContentProcessor: Feed not found for list_source {$listSource->id}.");
            return $stats;
        }

        // ── Load or create tracking record ────────────────────────────────────
        $tracking = ListSourceTracking::findOrCreateFor($listSource->id);

        // ── Resolve the list owner's user_id ─────────────────────────────────
        $userId = DB::table('list_sources')
            ->join('lists', 'lists.id', '=', 'list_sources.list_id')
            ->where('list_sources.id', $listSource->id)
            ->value('lists.user_id');

        // ── Determine first run vs regular run ────────────────────────────────
        // A bookmark's existence is the authoritative signal. See the class
        // docblock for the rationale behind this choice.
        $bookmark = ContentAlreadyProcessed::findBookmark($listSource->id);

        if ($bookmark === null) {
            // ── FIRST RUN ─────────────────────────────────────────────────────
            Log::info("TextBasedRssContentProcessor: First run detected for list_source {$listSource->id}.");
            $stats = $this->firstRunProcessing($listSource, $feed, $userId, $stats);
        } else {
            // ── REGULAR RUN ───────────────────────────────────────────────────
            Log::info("TextBasedRssContentProcessor: Regular run detected for list_source {$listSource->id}. Bookmark: {$bookmark->source_url}");
            $stats = $this->regularRunProcessing($listSource, $feed, $userId, $bookmark, $stats);
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
     * Applies a lookback window to avoid flooding the summaries table with
     * back-catalogue content. Only items published within the window are
     * processed.
     *
     * After processing, if at least one item was processed, we INSERT a new
     * bookmark. This is an INSERT, not a rotation — no existing record to delete.
     *
     * If nothing was processed, no bookmark is inserted and the next run is
     * also treated as a first run.
     */
    private function firstRunProcessing(
        object           $listSource,
        TextBasedRssFeed $feed,
        int              $userId,
        array            $stats,
    ): array {
        // ── Fetch and parse the feed ──────────────────────────────────────────
        $entries = $this->fetchFeedEntries($feed->rss_url);

        if ($entries === null) {
            $this->recordFetchFailure($listSource, $feed->title);
            $stats['errors']++;
            return $stats;
        }

        $stats['fetched']   = count($entries);
        $lookbackDays       = config('processing.first_run_lookback_days', 2);
        $newestProcessedUrl = null;

        // ── Walk items newest-first ───────────────────────────────────────────
        foreach ($entries as $entry) {
            $sourceUrl   = $entry['url'];
            $publishedAt = $entry['published_at'];

            // ── Lookback window check ─────────────────────────────────────────
            // Items with no published_at are not skipped — we cannot determine
            // their age, so we process them to be safe.
            if ($publishedAt && $publishedAt->lt(now()->subDays($lookbackDays))) {
                $stats['skipped']++;
                continue;
            }

            // ── Process this item ─────────────────────────────────────────────
            try {
                $this->processEntry(
                    listSource:  $listSource,
                    entry:       $entry,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;

                // Capture the newest URL processed — this becomes the bookmark.
                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("TextBasedRssContentProcessor: Failed to process entry '{$sourceUrl}'", [
                    'list_source_id' => $listSource->id,
                    'error'          => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // ── Insert bookmark if anything was processed ─────────────────────────
        if ($newestProcessedUrl !== null) {
            ContentAlreadyProcessed::rotateBookmark(
                listSourceId: $listSource->id,
                userId:       $userId,
                sourceUrl:    $newestProcessedUrl,
            );
            Log::info("TextBasedRssContentProcessor: First run complete. Bookmark inserted: {$newestProcessedUrl}");
        } else {
            Log::info("TextBasedRssContentProcessor: First run complete. No items processed — no bookmark inserted.");
        }

        return $stats;
    }

    // =========================================================================
    // Regular Run Processing
    // =========================================================================

    /**
     * Handle a regular (non-first) processing run for a list_source.
     *
     * Walks the feed newest-first and stops as soon as a stop condition is met.
     *
     * TWO STOP CONDITIONS:
     *
     * 1. NORMAL STOP: The current item's URL matches the bookmark URL.
     *    This is the expected case on every regular run after the first.
     *
     * 2. FALLBACK STOP: The current item's published_at is older than the
     *    bookmark's processed_at. Handles the case where the bookmarked item
     *    has been removed from the feed. We've passed the point in the feed
     *    where the bookmark would have appeared.
     *
     * After processing, if anything was processed, we rotate the bookmark:
     * DELETE the old record, INSERT a new one. If nothing was processed,
     * the bookmark is left completely unchanged.
     */
    private function regularRunProcessing(
        object                  $listSource,
        TextBasedRssFeed        $feed,
        int                     $userId,
        ContentAlreadyProcessed $bookmark,
        array                   $stats,
    ): array {
        // ── Fetch and parse the feed ──────────────────────────────────────────
        $entries = $this->fetchFeedEntries($feed->rss_url);

        if ($entries === null) {
            $this->recordFetchFailure($listSource, $feed->title);
            $stats['errors']++;
            return $stats;
        }

        $stats['fetched']   = count($entries);
        $newestProcessedUrl = null;

        // ── Walk items newest-first ───────────────────────────────────────────
        foreach ($entries as $entry) {
            $sourceUrl   = $entry['url'];
            $publishedAt = $entry['published_at'];

            // ── STOP CONDITION 1: Bookmark URL match ──────────────────────────
            if ($sourceUrl === $bookmark->source_url) {
                Log::info("TextBasedRssContentProcessor: Reached bookmark URL. Stopping.");
                break;
            }

            // ── STOP CONDITION 2: Item older than bookmark's processed_at ─────
            if ($publishedAt && $publishedAt->lt($bookmark->processed_at)) {
                Log::info("TextBasedRssContentProcessor: Item older than bookmark processed_at. Fallback stop triggered.");
                break;
            }

            // ── Process this item ─────────────────────────────────────────────
            try {
                $this->processEntry(
                    listSource:  $listSource,
                    entry:       $entry,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;

                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("TextBasedRssContentProcessor: Failed to process entry '{$sourceUrl}'", [
                    'list_source_id' => $listSource->id,
                    'error'          => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // ── Rotate bookmark if anything was processed ─────────────────────────
        if ($newestProcessedUrl !== null) {
            ContentAlreadyProcessed::rotateBookmark(
                listSourceId: $listSource->id,
                userId:       $userId,
                sourceUrl:    $newestProcessedUrl,
            );
            Log::info("TextBasedRssContentProcessor: Regular run complete. Bookmark rotated to: {$newestProcessedUrl}");
        } else {
            Log::info("TextBasedRssContentProcessor: Regular run complete. Nothing new — bookmark unchanged.");
        }

        return $stats;
    }

    // =========================================================================
    // RSS Fetching & Parsing
    // =========================================================================

    /**
     * Fetch the RSS/Atom feed and parse all entries into normalised arrays.
     * Returns null on fetch failure or invalid XML.
     */
    private function fetchFeedEntries(string $rssUrl): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept'     => 'application/rss+xml, application/xml, text/xml, */*',
                    'User-Agent' => config('app.name', 'Laravel') . ' RSS Reader',
                ])
                ->get($rssUrl);
        } catch (\Throwable $e) {
            Log::warning("TextBasedRssContentProcessor: HTTP request failed for '{$rssUrl}': {$e->getMessage()}");
            return null;
        }

        if ($response->failed()) {
            Log::warning("TextBasedRssContentProcessor: Feed returned HTTP {$response->status()} for '{$rssUrl}'");
            return null;
        }

        $body = trim($response->body());

        if (empty($body)) {
            Log::warning("TextBasedRssContentProcessor: Empty response for '{$rssUrl}'");
            return null;
        }

        $previousErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body);

            if ($xml === false) {
                Log::warning("TextBasedRssContentProcessor: Invalid XML for '{$rssUrl}'");
                return null;
            }

            $rootName = strtolower($xml->getName());

            if ($rootName === 'feed') {
                return $this->parseAtomEntries($xml);
            }

            if ($rootName === 'rss' || $rootName === 'rdf:rdf' || $rootName === 'rdf') {
                $channel = $xml->channel ?? $xml;
                return $this->parseRssEntries($channel);
            }

            Log::warning("TextBasedRssContentProcessor: Unrecognised feed root '<{$rootName}>' for '{$rssUrl}'");
            return null;

        } finally {
            // Always restore libxml state to avoid polluting subsequent XML operations.
            libxml_use_internal_errors($previousErrors);
            libxml_clear_errors();
        }
    }

    /**
     * Parse entries from an RSS 2.0 <channel>.
     */
    private function parseRssEntries(\SimpleXMLElement $channel): array
    {
        $entries = [];

        foreach ($channel->item as $item) {
            $ns      = $item->getNamespaces(true);
            $content = isset($ns['content'])
                ? (string) $item->children($ns['content'])->encoded
                : null;

            $url = trim((string) ($item->link ?? ''));

            if (empty($url)) {
                continue;
            }

            $entries[] = [
                'url'          => $url,
                'title'        => trim((string) ($item->title ?? 'Untitled')),
                'description'  => $this->cleanText((string) ($item->description ?? '')),
                'content'      => $content ? $this->cleanText($content) : null,
                'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
            ];
        }

        return $entries;
    }

    /**
     * Parse entries from an Atom <feed>.
     */
    private function parseAtomEntries(\SimpleXMLElement $feed): array
    {
        $entries = [];

        foreach ($feed->entry as $entry) {
            $url = null;

            foreach ($entry->link as $link) {
                $rel = (string) ($link->attributes()['rel'] ?? 'alternate');
                if ($rel === 'alternate' || $rel === '') {
                    $url = (string) $link->attributes()['href'];
                    break;
                }
            }

            if (empty($url)) {
                continue;
            }

            $entries[] = [
                'url'          => $url,
                'title'        => trim((string) ($entry->title ?? 'Untitled')),
                'description'  => isset($entry->summary) ? $this->cleanText((string) $entry->summary) : null,
                'content'      => isset($entry->content) ? $this->cleanText((string) $entry->content) : null,
                'published_at' => $this->parseDate((string) ($entry->updated ?? $entry->published ?? '')),
            ];
        }

        return $entries;
    }

    // =========================================================================
    // Entry Processing
    // =========================================================================

    /**
     * Process a single feed entry based on the list_source's processing_mode.
     * Called by both firstRunProcessing() and regularRunProcessing().
     */
    private function processEntry(
        object $listSource,
        array  $entry,
        Carbon $publishedAt,
        int    $userId,
    ): void {
        $url         = $entry['url'];
        $title       = $entry['title'];
        $description = $entry['description'] ?? '';

        [$summaryHtml, $isRelevant] = match ($listSource->processing_mode) {
            'description' => [
                $description ? '<p>' . nl2br(e($description)) . '</p>' : null,
                true,
            ],
            'summary' => [
                $this->summarise($url, $title, $entry),
                true,
            ],
            'search' => array_values(
                $this->searchAndSummarise($url, $title, $description, $entry, $listSource->search_terms)
            ),
            default => [null, true],
        };

        DB::table('summaries')->insert([
            'user_id'               => $userId,
            'list_source_id'        => $listSource->id,
            'source_url'            => $url,
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
    // Feed Fetch Failure Handler
    // =========================================================================

    /**
     * Record a feed fetch failure and optionally suspend the source.
     * Extracted so both run methods can use identical failure handling.
     */
    private function recordFetchFailure(object $listSource, string $feedTitle): void
    {
        $tracking = ListSourceTracking::findOrCreateFor($listSource->id);
        $tracking->recordFailure('Failed to fetch or parse RSS feed.');

        if ($tracking->shouldSuspend()) {
            DB::table('list_sources')->where('id', $listSource->id)->update(['suspended' => true]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'text_based_rss',
                title: "RSS feed '{$feedTitle}' suspended",
                message: "Feed '{$feedTitle}' has failed {$tracking->consecutive_failures} consecutive times and has been auto-suspended from list_source {$listSource->id}.",
            );
        }
    }

    // =========================================================================
    // LLM: Summary
    // =========================================================================

    /**
     * Extract article text via Readability and summarise via the configured LLM.
     * Falls back to feed content or description if article extraction fails.
     */
    private function summarise(string $url, string $title, array $entry): string
    {
        $text = $this->extractor->fetchArticleText($url)
            ?? $entry['content']
            ?? $entry['description']
            ?? null;

        if (empty($text)) {
            return '<p><em>Article content unavailable for summarisation.</em></p>';
        }

        $prompt = str_replace('{title}', $title, config('prompts.article_summary'))
            . $text;

        try {
            return $this->llm->generateContent(self::USE_CASE_SLUG, $prompt);

        } catch (LlmAuthenticationException $e) {
            Log::error("LLM auth error for article '{$url}'", ['error' => $e->getMessage()]);

            AdminAlert::raiseIfNew(
                tier: 3,
                category: 'llm',
                title: "{$e->providerSlug} API key invalid",
                message: "Authentication failed with {$e->providerSlug} provider. Check the API key in .env.",
            );

            return '<p><em>Summary generation failed — authentication error.</em></p>';

        } catch (LlmRateLimitException $e) {
            Log::warning("LLM rate limited for article '{$url}'", ['error' => $e->getMessage()]);
            return '<p><em>Summary generation deferred — rate limited. Will retry on next run.</em></p>';

        } catch (LlmException $e) {
            Log::error("LLM error for article '{$url}'", ['error' => $e->getMessage()]);
            return '<p><em>Summary generation failed. Will retry on next run.</em></p>';
        }
    }

    // =========================================================================
    // LLM: Search + Summarise
    // =========================================================================

    /**
     * Tiered search: title → description → LLM semantic relevance check.
     */
    private function searchAndSummarise(
        string  $url,
        string  $title,
        string  $description,
        array   $entry,
        ?string $searchTerms,
    ): array {
        if (empty($searchTerms)) {
            return ['summary' => null, 'is_relevant' => false];
        }

        $terms = array_map('trim', explode(',', $searchTerms));

        if ($this->matchesTerms($title, $terms)) {
            return ['summary' => $this->summarise($url, $title, $entry), 'is_relevant' => true];
        }

        if ($description && $this->matchesTerms($description, $terms)) {
            return ['summary' => $this->summarise($url, $title, $entry), 'is_relevant' => true];
        }

        $text = $this->extractor->fetchArticleText($url)
            ?? $entry['content']
            ?? $entry['description']
            ?? null;

        if (empty($text)) {
            return ['summary' => null, 'is_relevant' => false];
        }

        $prompt = str_replace(
            ['{search_terms}', '{title}'],
            [$searchTerms,     $title],
            config('prompts.article_search')
        ) . $text;

        try {
            $result = trim($this->llm->generateContent(self::USE_CASE_SLUG, $prompt));

            if ($result === 'NOT_RELEVANT') {
                return ['summary' => null, 'is_relevant' => false];
            }

            return ['summary' => $result, 'is_relevant' => true];

        } catch (LlmException $e) {
            Log::error("LLM search+summarise failed for article '{$url}'", ['error' => $e->getMessage()]);
            return ['summary' => null, 'is_relevant' => false];
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Strip HTML tags and normalise whitespace. */
    private function cleanText(string $text): ?string
    {
        $clean = trim(strip_tags($text));
        $clean = preg_replace('/\s+/', ' ', $clean);
        return $clean !== '' ? $clean : null;
    }

    /** Parse a date string into a Carbon instance, returning null if unparseable. */
    private function parseDate(string $dateStr): ?Carbon
    {
        if (empty(trim($dateStr))) { return null; }
        try {
            return Carbon::parse($dateStr);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Case-insensitive check: does any term appear in the given text? */
    private function matchesTerms(string $text, array $terms): bool
    {
        $lower = strtolower($text);
        foreach ($terms as $term) {
            if (str_contains($lower, strtolower($term))) { return true; }
        }
        return false;
    }
}