<?php

// tests/Feature/Processing/simulate_youtube_regular_run_processing.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Youtube\Services\YoutubeContentProcessor;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\Support\FakeYoutubePlaylistBuilder;

/**
 * Simulation: YouTube Regular Run Processing
 *
 * Tests all regular-run scenarios, including the critical end-to-end chain
 * that reproduces the bug discovered during manual UI testing.
 *
 * Covers test cases 8–34 from README_PROCESSING_TEST_CASES.md.
 */

uses(RefreshDatabase::class);

const YT_REG_API_BASE = 'www.googleapis.com/youtube/v3/playlistItems*';

beforeEach(function () {
    config(['processing.first_run_lookback_days' => 7]);

    $this->user    = User::factory()->create();
    $this->list    = ListModel::factory()->forUser($this->user)->create();
    $this->channel = YoutubeChannel::factory()->forUser($this->user)->create();

    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => $this->channel->id,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->listSource = DB::table('list_sources')
        ->where('sourceable_id', $this->channel->id)
        ->first();

    $this->llmMock = mock(LlmService::class);
    app()->instance(LlmService::class, $this->llmMock);
    $this->processor = new YoutubeContentProcessor($this->llmMock);

    Process::fake();
});

// =============================================================================
// Test Case 8: Correctly routes to regularRunProcessing() when bookmark exists
// =============================================================================

it('[TC8] routes to regular run when bookmark exists', function () {
    // Insert a bookmark — this signals a regular run.
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(7)
    );

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subDays(7),
    ]);

    // Feed with no new items — all 50 items are same as before.
    // If regularRunProcessing() runs, it stops at the bookmark immediately.
    // If firstRunProcessing() ran instead, it would apply lookback and process 7.
    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    // Regular run: stops at bookmark (item 7), processes items 1–6 (6 new items).
    expect($stats['processed'])->toBe(6);
});

// =============================================================================
// Test Case 9: Stops immediately at bookmark URL — processes zero items
// =============================================================================

it('[TC9] stops immediately at bookmark url when first item is the bookmark', function () {
    // Bookmark points to the newest item in the feed (item 1).
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(1)
    );

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    expect($stats['errors'])->toBe(0);
    expect(DB::table('summaries')->where('list_source_id', $this->listSource->id)->count())->toBe(0);
});

// =============================================================================
// Test Case 10: Bookmark is unchanged after a zero-result run
// =============================================================================

it('[TC10] bookmark is unchanged after a zero-result regular run', function () {
    $bookmarkUrl     = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));
    $originalTime    = now()->subHour();

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => $originalTime,
    ]);

    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);

    // Bookmark URL must be unchanged.
    expect($bookmark->source_url)->toBe($bookmarkUrl);
    // Only one bookmark row — no rotation occurred.
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// Test Case 11: Stats show 0 processed, 0 skipped, 0 errors on zero-result run
// =============================================================================

it('[TC11] stats are all zero when nothing new to process', function () {
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    expect($stats['skipped'])->toBe(0);
    expect($stats['errors'])->toBe(0);
    expect($stats['fetched'])->toBe(50);
});

// =============================================================================
// Test Case 12: Idempotency — second zero-result run behaves identically to first
// =============================================================================

it('[TC12] second zero-result run is identical to first zero-result run', function () {
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    // Run 1: zero result.
    Http::fake([YT_REG_API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
    $stats1 = $this->processor->process($this->listSource);

    // Run 2: same feed, zero result again.
    Http::fake([YT_REG_API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
    $stats2 = $this->processor->process($this->listSource);

    expect($stats1)->toBe($stats2);
    expect(ContentAlreadyProcessed::count())->toBe(1);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkUrl);
});

// =============================================================================
// Test Cases 13–18: Regular run with 5 new items prepended
// =============================================================================

it('[TC13-18] processes exactly 5 new items when 5 are prepended to the feed', function () {
    // The original 50-item feed — newest item published 10 days ago, each 1 day apart.
    // This means item 1 = 10 days ago, item 50 = 59 days ago.
    // The bookmark points to item 1 with processed_at = 10 days ago.
    $originalFeedResponse = FakeYoutubePlaylistBuilder::build(50, now()->subDays(10), 1, 'vid');

    $originalBookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(1, 'vid')
    );

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $originalBookmarkUrl,
        'processed_at'   => now()->subDays(10),
    ]);

    // New items published today — clearly newer than the bookmark (10 days ago).
    $newItemsResponse = FakeYoutubePlaylistBuilder::build(5, now(), 1, 'new');

    // Merge: new items first (newest), then original items.
    $combinedResponse = [
        'kind'     => 'youtube#playlistItemListResponse',
        'etag'     => 'fake',
        'pageInfo' => ['totalResults' => 55, 'resultsPerPage' => 50],
        'items'    => array_merge($newItemsResponse['items'], $originalFeedResponse['items']),
    ];

    Http::fake([YT_REG_API_BASE => Http::response($combinedResponse)]);

    $stats = $this->processor->process($this->listSource);

    // TC13: Exactly 5 new items processed.
    expect($stats['processed'])->toBe(5);

    // TC14 + TC15: Stopped at the bookmark URL, bookmark item itself not processed.
    $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

    // TC16: Nothing after the bookmark was processed.
    $afterBookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(2, 'vid'));
    $this->assertDatabaseMissing('summaries', ['source_url' => $afterBookmarkUrl]);

    // TC17: Bookmark rotated to the newest new item (new_001).
    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    $expectedNewBookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
    expect($newBookmark->source_url)->toBe($expectedNewBookmarkUrl);
    expect(ContentAlreadyProcessed::count())->toBe(1);

    // TC18: Stats show exactly 5 processed.
    expect($stats['processed'])->toBe(5);
    expect($stats['errors'])->toBe(0);
});

// =============================================================================
// Test Cases 19–21: Bookmark URL has disappeared from feed (fallback stop)
// =============================================================================

it('[TC19-21] fallback stop when bookmark url is gone from feed', function () {
    // Bookmark points to a video that has since been deleted from YouTube.
    $deletedVideoUrl = 'https://www.youtube.com/watch?v=deleted_video';
    $bookmarkTime    = now()->subDays(3);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $deletedVideoUrl,
        'processed_at'   => $bookmarkTime,
    ]);

    // Feed has 3 new items (newer than bookmark) + 50 old items.
    // The deleted video is not in the feed at all.
    $newItems      = FakeYoutubePlaylistBuilder::build(3,  now(),                 1, 'new');
    $oldItems      = FakeYoutubePlaylistBuilder::build(50, $bookmarkTime->subDay(), 1, 'old');

    $combinedResponse = [
        'kind'     => 'youtube#playlistItemListResponse',
        'etag'     => 'fake',
        'pageInfo' => ['totalResults' => 53, 'resultsPerPage' => 50],
        'items'    => array_merge($newItems['items'], $oldItems['items']),
    ];

    Http::fake([YT_REG_API_BASE => Http::response($combinedResponse)]);

    $stats = $this->processor->process($this->listSource);

    // TC19 + TC20: Stopped at fallback, processed only the 3 new items.
    expect($stats['processed'])->toBe(3);

    // TC21: Bookmark rotated to newest new item.
    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
    expect($newBookmark->source_url)->toBe($expectedUrl);
});

// =============================================================================
// Test Cases 22–23: Items with no published_at in regular run
// =============================================================================

it('[TC22-23] processes items with no published_at and sets bookmark correctly', function () {
    // Build a feed where the newest item has no published date.
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subDays(2),
    ]);

    // New item with no published date prepended before the bookmark.
    $noDateItem = [
        'kind'   => 'youtube#playlistItem',
        'etag'   => 'fake-no-date',
        'snippet' => [
            // No publishedAt
            'title'       => 'No Date Video',
            'description' => 'This video has no published date.',
            'resourceId'  => ['kind' => 'youtube#video', 'videoId' => 'no_date_vid'],
        ],
        'contentDetails' => [
            'videoId' => 'no_date_vid',
            // No videoPublishedAt
        ],
    ];

    $originalFeed = FakeYoutubePlaylistBuilder::build(50, now()->subDay(), 1);

    $combinedResponse = [
        'kind'     => 'youtube#playlistItemListResponse',
        'etag'     => 'fake',
        'pageInfo' => ['totalResults' => 51, 'resultsPerPage' => 50],
        'items'    => array_merge([$noDateItem], $originalFeed['items']),
    ];

    Http::fake([YT_REG_API_BASE => Http::response($combinedResponse)]);

    $stats = $this->processor->process($this->listSource);

    // TC22: The no-date item should be processed (can't determine age, so process it).
    // It appears before the bookmark in the feed, so it gets processed.
    expect($stats['processed'])->toBeGreaterThanOrEqual(1);
    expect($stats['errors'])->toBe(0);
});

// =============================================================================
// Test Cases 24–25: Edge cases
// =============================================================================

it('[TC24] feed fetch failure leaves bookmark unchanged and records error', function () {
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subDay(),
    ]);

    Http::fake([YT_REG_API_BASE => Http::response('Server Error', 500)]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['errors'])->toBe(1);
    expect($stats['processed'])->toBe(0);

    // Bookmark must be completely unchanged.
    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark->source_url)->toBe($bookmarkUrl);
});

it('[TC25] zero-item feed leaves bookmark unchanged with no errors', function () {
    $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subDay(),
    ]);

    Http::fake([
        YT_REG_API_BASE => Http::response(['kind' => 'youtube#playlistItemListResponse', 'items' => []]),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(0);
    expect($stats['processed'])->toBe(0);
    expect($stats['errors'])->toBe(0);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkUrl);
});

it('[TC26] all 50 items are new — all processed, bookmark set to item 1', function () {
    // Bookmark points to a video not in the current feed (simulates
    // a completely fresh channel with all new content).
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://www.youtube.com/watch?v=old_video_not_in_feed',
        'processed_at'   => now()->subDays(60),
    ]);

    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    // All 50 items processed (bookmark URL never found, fallback stop at end).
    expect($stats['processed'])->toBe(50);

    // Bookmark set to item 1 (the newest).
    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));
    expect($bookmark->source_url)->toBe($expectedUrl);
});

it('[TC27] single item feed — first run processes it, regular run stops immediately', function () {
    // Part 1: first run with single-item feed.
    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(1, now(), 1)
        ),
    ]);

    $firstRunStats = $this->processor->process($this->listSource);
    expect($firstRunStats['processed'])->toBe(1);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark)->not->toBeNull();

    // Part 2: regular run — same single-item feed.
    Http::fake([
        YT_REG_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(1, now(), 1)
        ),
    ]);

    $regularRunStats = $this->processor->process($this->listSource);
    expect($regularRunStats['processed'])->toBe(0);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

it('[TC28] two list_sources on same channel have independent bookmarks', function () {
    $list2 = ListModel::factory()->forUser($this->user)->create();

    DB::table('list_sources')->insert([
        'list_id'         => $list2->id,
        'sourceable_id'   => $this->channel->id,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $listSource2 = DB::table('list_sources')->where('list_id', $list2->id)->first();

    // Run first run for both list_sources.
    Http::fake([YT_REG_API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
    $this->processor->process($this->listSource);

    Http::fake([YT_REG_API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
    $this->processor->process($listSource2);

    // Both have independent bookmarks.
    expect(ContentAlreadyProcessed::count())->toBe(2);

    $bookmark1 = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    $bookmark2 = ContentAlreadyProcessed::findBookmark($listSource2->id);

    expect($bookmark1->list_source_id)->toBe($this->listSource->id);
    expect($bookmark2->list_source_id)->toBe($listSource2->id);
    expect($bookmark1->source_url)->toBe($bookmark2->source_url); // Same URL, different list_sources.
});

// =============================================================================
// Test Cases 30–34: Full end-to-end chain
// This is the most important scenario — it reproduces the bug that was
// discovered during manual UI testing.
// =============================================================================

it('[TC30-34] full end-to-end chain: first run → 2 zero runs → new items → zero run', function () {
    $feed50 = FakeYoutubePlaylistBuilder::build(50, now()->subDays(1), 1);

   // $newItemsR4 = FakeYoutubePlaylistBuilder::build(5, now()->addDays(1), 1, 'new');
    $newItemsR4 = FakeYoutubePlaylistBuilder::build(5, now()->addDays(10), 1, 'new');
    $feed55 = [
        'kind'     => 'youtube#playlistItemListResponse',
        'etag'     => 'fake',
        'pageInfo' => ['totalResults' => 55, 'resultsPerPage' => 50],
        'items'    => array_merge($newItemsR4['items'], $feed50['items']),
    ];

    Http::fakeSequence()
        ->push($feed50)   // Run 1: first run
        ->push($feed50)   // Run 2: zero result
        ->push($feed50)   // Run 3: zero result
        ->push($feed55)   // Run 4: 5 new items
        ->push($feed55);  // Run 5: zero result

    // ── Run 1: First run ──────────────────────────────────────────────────────
    $run1Stats = $this->processor->process($this->listSource);

    expect($run1Stats['processed'])->toBeGreaterThanOrEqual(1, 'Run 1: should process at least 1 item');
    expect($run1Stats['skipped'])->toBeGreaterThanOrEqual(1, 'Run 1: should skip at least 1 item');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->not->toBeNull('Run 1: bookmark should be set');

    $bookmarkAfterRun1 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;

    // ── Run 2: Regular run, nothing new ──────────────────────────────────────
    $run2Stats = $this->processor->process($this->listSource);

    expect($run2Stats['processed'])->toBe(0, 'Run 2: THE CRITICAL CHECK — must process 0, not 43');
    expect($run2Stats['errors'])->toBe(0, 'Run 2: no errors');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)
        ->toBe($bookmarkAfterRun1, 'Run 2: bookmark must be unchanged');

    // ── Run 3: Regular run, same feed again ───────────────────────────────────
    $run3Stats = $this->processor->process($this->listSource);

    expect($run3Stats['processed'])->toBe(0, 'Run 3: should still process 0');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)
        ->toBe($bookmarkAfterRun1, 'Run 3: bookmark still unchanged');

    // ── Run 4: 5 new items prepended ─────────────────────────────────────────
    $run4Stats = $this->processor->process($this->listSource);

    expect($run4Stats['processed'])->toBe(5, 'Run 4: should process exactly 5 new items');
    expect($run4Stats['errors'])->toBe(0, 'Run 4: no errors');

    $bookmarkAfterRun4 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
    expect($bookmarkAfterRun4)->not->toBe($bookmarkAfterRun1, 'Run 4: bookmark must have rotated');

    $expectedNewBookmark = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
    expect($bookmarkAfterRun4)->toBe($expectedNewBookmark, 'Run 4: bookmark points to newest new item');

    // ── Run 5: Nothing new ────────────────────────────────────────────────────
    $run5Stats = $this->processor->process($this->listSource);

    expect($run5Stats['processed'])->toBe(0, 'Run 5: should process 0 after new items absorbed');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)
        ->toBe($bookmarkAfterRun4, 'Run 5: bookmark unchanged');
});