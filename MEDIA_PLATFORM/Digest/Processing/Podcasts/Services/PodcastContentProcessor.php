<?php

namespace MediaPlatform\Digest\Processing\Podcasts\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\Processing\Contracts\ContentProcessorInterface;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Models\ListSourceTracking;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PodcastContentProcessor
 *
 * !! READ BEFORE MODIFYING !!
 * ════════════════════════════════════════════════════════════════════════════
 * CORE ASSUMPTION: Podcast RSS feeds list episodes newest-first.
 * This processor relies on that sort order to stop processing as soon as it
 * reaches an episode it has already seen.
 *
 * See app/Processing/README_PROCESSING_ASSUMPTIONS.md for the full design
 * rationale, bookmark strategy, and edge case documentation.
 *
 * PROCESSING FLOW
 * ───────────────
 * process()
 *   └── Loads podcast, tracking, and bookmark.
 *   └── Determines first run vs regular run based on bookmark existence.
 *   └── Delegates to firstRunProcessing() or regularRunProcessing().
 *   └── Records tracking success.
 *
 * firstRunProcessing()
 *   └── Fetches and parses the RSS feed.
 *   └── Walks episodes newest-first.
 *   └── Skips episodes older than the lookback window.
 *   └── Processes everything within the lookback window.
 *   └── INSERTS a new bookmark pointing to the newest processed episode.
 *   └── No bookmark exists yet — this is an INSERT, not a rotation.
 *
 * regularRunProcessing()
 *   └── Fetches and parses the RSS feed.
 *   └── Walks episodes newest-first.
 *   └── STOPS at the bookmarked episode URL (normal case).
 *   └── STOPS when an episode's published_at is older than the bookmark's
 *       processed_at (bookmarked episode deleted/pulled — fallback stop).
 *   └── Processes everything before the stop point.
 *   └── If anything was processed: DELETE old bookmark, INSERT new bookmark
 *       pointing to the newest episode processed this run (rotation).
 *   └── If nothing was processed: bookmark is left completely unchanged.
 * ════════════════════════════════════════════════════════════════════════════
 */
class PodcastContentProcessor implements ContentProcessorInterface
{
    private const USE_CASE_SLUG = 'digest-processing';

    private const NAMESPACES = [
        'itunes'  => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
        'content' => 'http://purl.org/rss/1.0/modules/content/',
        'podcast' => 'https://podcastindex.org/namespace/1.0',
    ];

    private const SEARCHABLE_FIELDS = [
        'title',
        'content:encoded',
        'description',
        'itunes:summary',
        'itunes:subtitle',
        'itunes:keywords',
    ];

    private const DISPLAY_FIELD_PRIORITY = [
        'content:encoded',
        'itunes:summary',
        'description',
        'itunes:subtitle',
    ];

    public function __construct(
        private LlmService $llm,
    ) {}

    // =========================================================================
    // Entry Point
    // =========================================================================

    /**
     * Process a single list_source row for a podcast.
     *
     * Determines whether this is a first run (no bookmark exists) or a regular
     * run (bookmark exists), then delegates accordingly. Feed fetching happens
     * inside each run method so the two paths are completely self-contained.
     */
    public function process(object $listSource): array
    {
        $stats = ['fetched' => 0, 'processed' => 0, 'skipped' => 0, 'errors' => 0];

        // ── Load the podcast record ───────────────────────────────────────────
        $podcast = Podcast::find($listSource->sourceable_id);

        if (! $podcast) {
            Log::error("PodcastContentProcessor: Podcast not found for list_source {$listSource->id}.");
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
            Log::info("PodcastContentProcessor: First run detected for list_source {$listSource->id}.");
            $stats = $this->firstRunProcessing($listSource, $podcast, $userId, $stats);
        } else {
            // ── REGULAR RUN ───────────────────────────────────────────────────
            Log::info("PodcastContentProcessor: Regular run detected for list_source {$listSource->id}. Bookmark: {$bookmark->source_url}");
            $stats = $this->regularRunProcessing($listSource, $podcast, $userId, $bookmark, $stats);
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
     * back-catalogue content. Only episodes published within the window are
     * processed.
     *
     * After processing, if at least one episode was processed, we INSERT a new
     * bookmark. This is an INSERT, not a rotation — no existing record to delete.
     *
     * If nothing was processed, no bookmark is inserted and the next run is
     * also treated as a first run.
     */
    private function firstRunProcessing(
        object  $listSource,
        Podcast $podcast,
        int     $userId,
        array   $stats,
    ): array {
        // ── Fetch and parse the RSS feed ──────────────────────────────────────
        $items = $this->fetchFeedItems($podcast->rss_url);

        if ($items === null) {
            $this->recordFetchFailure($listSource, $podcast->title);
            $stats['errors']++;
            return $stats;
        }

        // Items dropped at parse time (no source_url) are counted as skipped.
        $stats['skipped'] += $items['skipped'];
        $parsedItems       = $items['items'];
        $stats['fetched']  = count($parsedItems) + $items['skipped'];

        $lookbackDays       = config('processing.first_run_lookback_days', 2);
        $newestProcessedUrl = null;

        // ── Walk episodes newest-first ────────────────────────────────────────
        foreach ($parsedItems as $item) {
            $sourceUrl = $item['source_url'] ?? null;

            if (empty($sourceUrl)) {
                Log::warning("PodcastContentProcessor: Item has no source_url. Skipping.");
                $stats['skipped']++;
                continue;
            }

            $publishedAt = $item['published_at'];

            // ── Lookback window check ─────────────────────────────────────────
            // Items with no published_at are not skipped — we cannot determine
            // their age, so we process them to be safe.
            if ($publishedAt && $publishedAt->lt(now()->subDays($lookbackDays))) {
                $stats['skipped']++;
                continue;
            }

            // ── Process this episode ──────────────────────────────────────────
            try {
                $this->processItem(
                    listSource:  $listSource,
                    item:        $item,
                    sourceUrl:   $sourceUrl,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;

                // Capture the newest URL processed — this becomes the bookmark.
                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("PodcastContentProcessor: Failed to process item '{$sourceUrl}'", [
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
            Log::info("PodcastContentProcessor: First run complete. Bookmark inserted: {$newestProcessedUrl}");
        } else {
            Log::info("PodcastContentProcessor: First run complete. No items processed — no bookmark inserted.");
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
     *    bookmark's processed_at. Handles the case where the bookmarked episode
     *    was pulled from the feed (e.g. a retracted episode). We've passed
     *    the point in the feed where the bookmark would have appeared.
     *
     * After processing, if anything was processed, we rotate the bookmark:
     * DELETE the old record, INSERT a new one. If nothing was processed,
     * the bookmark is left completely unchanged.
     */
    private function regularRunProcessing(
        object                  $listSource,
        Podcast                 $podcast,
        int                     $userId,
        ContentAlreadyProcessed $bookmark,
        array                   $stats,
    ): array {
        // ── Fetch and parse the RSS feed ──────────────────────────────────────
        $items = $this->fetchFeedItems($podcast->rss_url);

        if ($items === null) {
            $this->recordFetchFailure($listSource, $podcast->title);
            $stats['errors']++;
            return $stats;
        }

        $stats['skipped'] += $items['skipped'];
        $parsedItems       = $items['items'];
        $stats['fetched']  = count($parsedItems) + $items['skipped'];

        $newestProcessedUrl = null;

        // ── Walk episodes newest-first ────────────────────────────────────────
        foreach ($parsedItems as $item) {
            $sourceUrl = $item['source_url'] ?? null;

            if (empty($sourceUrl)) {
                Log::warning("PodcastContentProcessor: Item has no source_url. Skipping.");
                $stats['skipped']++;
                continue;
            }

            $publishedAt = $item['published_at'];

            // ── STOP CONDITION 1: Bookmark URL match ──────────────────────────
            if ($sourceUrl === $bookmark->source_url) {
                Log::info("PodcastContentProcessor: Reached bookmark URL. Stopping.");
                break;
            }

            // ── STOP CONDITION 2: Item older than bookmark's processed_at ─────
            if ($publishedAt && $publishedAt->lt($bookmark->processed_at)) {
                Log::info("PodcastContentProcessor: Item older than bookmark processed_at. Fallback stop triggered.");
                break;
            }

            // ── Process this episode ──────────────────────────────────────────
            try {
                $this->processItem(
                    listSource:  $listSource,
                    item:        $item,
                    sourceUrl:   $sourceUrl,
                    publishedAt: $publishedAt ?? now(),
                    userId:      $userId,
                );
                $stats['processed']++;

                $newestProcessedUrl ??= $sourceUrl;

            } catch (\Throwable $e) {
                Log::error("PodcastContentProcessor: Failed to process item '{$sourceUrl}'", [
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
            Log::info("PodcastContentProcessor: Regular run complete. Bookmark rotated to: {$newestProcessedUrl}");
        } else {
            Log::info("PodcastContentProcessor: Regular run complete. Nothing new — bookmark unchanged.");
        }

        return $stats;
    }

    // =========================================================================
    // RSS Fetching & Parsing
    // =========================================================================

    /**
     * Fetch the RSS feed and parse all <item> elements into normalised arrays.
     * Returns null on fetch failure or invalid XML.
     */
    private function fetchFeedItems(string $rssUrl): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept'     => 'application/rss+xml, application/xml, text/xml, */*',
                    'User-Agent' => config('app.name', 'Laravel') . ' Podcast Reader',
                ])
                ->get($rssUrl);
        } catch (\Throwable $e) {
            Log::warning("PodcastContentProcessor: HTTP request failed for '{$rssUrl}': {$e->getMessage()}");
            return null;
        }

        if ($response->failed()) {
            Log::warning("PodcastContentProcessor: Feed returned HTTP {$response->status()} for '{$rssUrl}'");
            return null;
        }

        $body = trim($response->body());

        if (empty($body)) {
            Log::warning("PodcastContentProcessor: Empty response body for '{$rssUrl}'");
            return null;
        }

        // Suppress libxml errors internally and restore state in finally block.
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body);

            if ($xml === false) {
                Log::warning("PodcastContentProcessor: Invalid XML received from '{$rssUrl}'");
                return null;
            }

            $rootName = strtolower($xml->getName());

            if ($rootName !== 'rss') {
                Log::warning("PodcastContentProcessor: Expected RSS root, got '<{$rootName}>' for '{$rssUrl}'");
                return null;
            }

            $channel = $xml->channel;

            if (! $channel) {
                Log::warning("PodcastContentProcessor: No <channel> element for '{$rssUrl}'");
                return null;
            }

            return $this->parseItems($channel);

        } finally {
            libxml_use_internal_errors($previousErrors);
            libxml_clear_errors();
        }
    }

    /**
     * Parse all <item> elements in a <channel> into normalised arrays.
     */
    private function parseItems(\SimpleXMLElement $channel): array
    {
        $items        = [];
        $skippedCount = 0;

        foreach ($channel->item as $item) {
            $parsed = $this->parseItem($item);

            if ($parsed !== null) {
                $items[] = $parsed;
            } else {
                Log::warning("PodcastContentProcessor: Skipping item with no source_url.");
                $skippedCount++;
            }
        }

        return ['items' => $items, 'skipped' => $skippedCount];
    }

    /**
     * Parse a single <item> element into a normalised array.
     * Returns null if the item has no usable source_url.
     */
    private function parseItem(\SimpleXMLElement $item): ?array
    {
        $fields = [];

        foreach ($item->children() as $name => $child) {
            $value = trim((string) $child);
            if ($value !== '') {
                $fields[$name] = $value;
            }
        }

        $itunesChildren = $item->children(self::NAMESPACES['itunes']);

        foreach (['summary', 'subtitle', 'keywords', 'author', 'duration', 'episode', 'season'] as $fieldName) {
            if (isset($itunesChildren->$fieldName)) {
                $value = trim((string) $itunesChildren->$fieldName);
                if ($value !== '') {
                    $fields["itunes:{$fieldName}"] = $value;
                }
            }
        }

        $contentChildren = $item->children(self::NAMESPACES['content']);

        if (isset($contentChildren->encoded)) {
            $value = trim((string) $contentChildren->encoded);
            if ($value !== '') {
                $fields['content:encoded'] = $value;
            }
        }

        $transcriptUrl   = null;
        $podcastChildren = $item->children(self::NAMESPACES['podcast']);

        if (isset($podcastChildren->transcript)) {
            $attrs         = $podcastChildren->transcript->attributes();
            $transcriptUrl = (string) ($attrs['url'] ?? '');
        }

        $enclosure = null;

        if (isset($item->enclosure)) {
            $attrs     = $item->enclosure->attributes();
            $enclosure = [
                'url'    => (string) ($attrs['url']    ?? ''),
                'type'   => (string) ($attrs['type']   ?? ''),
                'length' => (string) ($attrs['length'] ?? ''),
            ];
        }

        // Prefer <link> for source_url, fall back to <guid>.
        $sourceUrl = trim($fields['link'] ?? '') ?: trim($fields['guid'] ?? '') ?: null;

        if ($sourceUrl === null) {
            return null;
        }

        $publishedAt = null;

        if (! empty($fields['pubDate'])) {
            try {
                $publishedAt = Carbon::parse($fields['pubDate']);
            } catch (\Throwable $e) {
                Log::warning("PodcastContentProcessor: Could not parse pubDate '{$fields['pubDate']}'.");
            }
        }

        return [
            'source_url'     => $sourceUrl,
            'title'          => $fields['title'] ?? 'Untitled',
            'published_at'   => $publishedAt,
            'enclosure'      => $enclosure,
            'transcript_url' => $transcriptUrl,
            'fields'         => $fields,
        ];
    }

    // =========================================================================
    // Item Processing
    // =========================================================================

    /**
     * Dispatch a single parsed episode to the correct processing mode method
     * and insert the result into the summaries table.
     *
     * Called by both firstRunProcessing() and regularRunProcessing().
     */
    private function processItem(
        object $listSource,
        array  $item,
        string $sourceUrl,
        Carbon $publishedAt,
        int    $userId,
    ): void {
        $title = $item['title'];

        [$summaryHtml, $isRelevant] = match ($listSource->processing_mode) {
            'description' => $this->processDescription($item),
            'summary'     => $this->processSummary($item),
            'search'      => array_values($this->processSearch($item, $listSource->search_terms)),
            default       => [null, true],
        };

        $sourceDescription = $this->selectDisplayField($item['fields']);
        $sourceDescription = $sourceDescription ? strip_tags($sourceDescription) : null;

        DB::table('summaries')->insert([
            'user_id'               => $userId,
            'list_source_id'        => $listSource->id,
            'source_url'            => $sourceUrl,
            'source_title'          => $title,
            'source_description'    => $sourceDescription,
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
    private function recordFetchFailure(object $listSource, string $podcastTitle): void
    {
        $tracking = ListSourceTracking::findOrCreateFor($listSource->id);
        $tracking->recordFailure('Failed to fetch or parse podcast RSS feed.');

        if ($tracking->shouldSuspend()) {
            DB::table('list_sources')
                ->where('id', $listSource->id)
                ->update(['suspended' => true]);

            AdminAlert::raiseIfNew(
                tier: 2,
                category: 'podcast',
                title: "Podcast '{$podcastTitle}' suspended",
                message: "Podcast '{$podcastTitle}' has failed {$tracking->consecutive_failures} consecutive times "
                       . "and has been auto-suspended from list_source {$listSource->id}.",
            );
        }
    }

    // =========================================================================
    // Processing Modes
    // =========================================================================

    /** Description mode: store the richest available text field verbatim. */
    private function processDescription(array $item): array
    {
        $content = $this->selectDisplayField($item['fields']);

        if (empty($content)) {
            return [null, true];
        }

        $html = $this->looksLikeHtml($content)
            ? $content
            : '<p>' . nl2br(e($content)) . '</p>';

        return [$html, true];
    }

    /** Summary mode: identical to description mode in v1. */
    private function processSummary(array $item): array
    {
        return $this->processDescription($item);
    }

    /** Search mode: string match across available fields, with transcript fallback. */
    private function processSearch(array $item, ?string $searchTerms): array
    {
        if (empty($searchTerms)) {
            return ['summary' => null, 'is_relevant' => false];
        }

        $terms = array_map('trim', explode(',', $searchTerms));

        foreach (self::SEARCHABLE_FIELDS as $fieldKey) {
            if (isset($item['fields'][$fieldKey]) && $this->matchesTerms($item['fields'][$fieldKey], $terms)) {
                return ['summary' => $this->buildDisplayHtml($item['fields']), 'is_relevant' => true];
            }
        }

        if (! empty($item['transcript_url'])) {
            $transcriptText = $this->fetchAndParseTranscript($item['transcript_url']);

            if ($transcriptText !== null && $this->matchesTerms($transcriptText, $terms)) {
                return ['summary' => $this->buildDisplayHtml($item['fields']), 'is_relevant' => true];
            }
        }

        return ['summary' => null, 'is_relevant' => false];
    }

    // =========================================================================
    // Transcript Fetching
    // =========================================================================

    /**
     * Fetch a .srt or .vtt transcript file and return it as plain text.
     */
    private function fetchAndParseTranscript(string $transcriptUrl): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept'     => 'text/vtt, text/plain, application/x-subrip, */*',
                    'User-Agent' => config('app.name', 'Laravel') . ' Podcast Reader',
                ])
                ->get($transcriptUrl);
        } catch (\Throwable $e) {
            Log::warning("PodcastContentProcessor: Transcript fetch failed for '{$transcriptUrl}': {$e->getMessage()}");
            return null;
        }

        if ($response->failed()) {
            Log::warning("PodcastContentProcessor: Transcript returned HTTP {$response->status()} for '{$transcriptUrl}'");
            return null;
        }

        $raw = trim($response->body());

        return empty($raw) ? null : $this->stripTranscriptTimestamps($raw);
    }

    /**
     * Strip timestamp lines, sequence numbers, and VTT headers from SRT/VTT content.
     */
    private function stripTranscriptTimestamps(string $raw): string
    {
        $lines  = preg_split('/\r?\n/', $raw);
        $spoken = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '')                                  { continue; }
            if (str_starts_with(strtoupper($line), 'WEBVTT')) { continue; }
            if (ctype_digit($line))                            { continue; }
            if (str_contains($line, '-->'))                    { continue; }
            $spoken[] = $line;
        }

        return implode(' ', $spoken);
    }

    // =========================================================================
    // Field Selection Helpers
    // =========================================================================

    /** Select the richest available display field from the item's fields map. */
    private function selectDisplayField(array $fields): ?string
    {
        foreach (self::DISPLAY_FIELD_PRIORITY as $key) {
            if (! empty($fields[$key])) {
                return $fields[$key];
            }
        }
        return null;
    }

    /** Build the HTML string for summary_html from the richest available field. */
    private function buildDisplayHtml(array $fields): ?string
    {
        $content = $this->selectDisplayField($fields);
        if (empty($content)) { return null; }
        return $this->looksLikeHtml($content) ? $content : '<p>' . nl2br(e($content)) . '</p>';
    }

    /** Returns true if the string appears to already contain HTML tags. */
    private function looksLikeHtml(string $content): bool
    {
        return (bool) preg_match('/<[a-z][\s\S]*>/i', $content);
    }

    /** Case-insensitive check: does any search term appear in the text? */
    private function matchesTerms(string $text, array $terms): bool
    {
        $lower = strtolower($text);
        foreach ($terms as $term) {
            if (! empty($term) && str_contains($lower, strtolower($term))) { return true; }
        }
        return false;
    }
}