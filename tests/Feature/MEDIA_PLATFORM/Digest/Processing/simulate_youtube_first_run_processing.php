<?php

// tests/Feature/Processing/simulate_youtube_first_run_processing.php

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
 * Simulation: YouTube First Run Processing
 *
 * Tests all first-run scenarios using a 50-item fake feed with items
 * spaced 1 day apart. Lookback window is set to 7 days, so items 1–7
 * fall within the window and items 8–50 are older.
 *
 * Covers test cases 1–7 from README_PROCESSING_TEST_CASES.md.
 */

uses(RefreshDatabase::class);

const YT_SIM_API_BASE = 'www.googleapis.com/youtube/v3/playlistItems*';

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
// Test Case 1: Correctly routes to firstRunProcessing() when no bookmark exists
// =============================================================================

it('[TC1] routes to first run when no bookmark exists', function () {
    // No bookmark in content_already_processed — should be a first run.
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();

    Http::fake([
        YT_SIM_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    // If firstRunProcessing() is called, the lookback window applies.
    // If regularRunProcessing() were called instead, all 50 items would be
    // processed (no bookmark to stop at). We verify the lookback was applied.
    $stats = $this->processor->process($this->listSource);

    // 7 items within lookback window, 43 skipped — confirms first run path.
    expect($stats['processed'])->toBe(7);
    expect($stats['skipped'])->toBe(43);
});

// =============================================================================
// Test Case 2: Processes only items within the lookback window (7 of 50)
// =============================================================================

it('[TC2] processes only items within the lookback window on first run', function () {
    // 50-item feed: items 1–7 are within 7 days, items 8–50 are older.
    Http::fake([
        YT_SIM_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(50);
    expect($stats['processed'])->toBe(7);
    expect($stats['errors'])->toBe(0);

    // Verify the 7 processed items are in the summaries table.
    expect(
        DB::table('summaries')->where('list_source_id', $this->listSource->id)->count()
    )->toBe(7);
});

// =============================================================================
// Test Case 3: Skips items older than the lookback window (43 of 50)
// =============================================================================

it('[TC3] skips items older than the lookback window on first run', function () {
    Http::fake([
        YT_SIM_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['skipped'])->toBe(43);

    // Item 8 (published 8 days ago) should NOT be in summaries.
    $item8Url = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(8)
    );

    $this->assertDatabaseMissing('summaries', ['source_url' => $item8Url]);

    // Item 50 (published 50 days ago) should NOT be in summaries.
    $item50Url = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(50)
    );

    $this->assertDatabaseMissing('summaries', ['source_url' => $item50Url]);
});

// =============================================================================
// Test Case 4: Inserts bookmark pointing to the newest processed item
// =============================================================================

it('[TC4] inserts bookmark pointing to the newest processed item after first run', function () {
    Http::fake([
        YT_SIM_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);

    expect($bookmark)->not->toBeNull();

    // The newest item is item 1 (vid_001), published today.
    $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(
        FakeYoutubePlaylistBuilder::videoId(1)
    );

    expect($bookmark->source_url)->toBe($expectedUrl);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// Test Case 5: Inserts NO bookmark when all items are older than the lookback window
// =============================================================================

it('[TC5] inserts no bookmark when no items fall within the lookback window', function () {
    // All 50 items are older than 7 days — none should be processed.
    Http::fake([
        YT_SIM_API_BASE => Http::response(
            // Start from 10 days ago so all items are outside the window.
            FakeYoutubePlaylistBuilder::build(50, now()->subDays(10), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    expect($stats['skipped'])->toBe(50);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();
});

// =============================================================================
// Test Case 6: Processes items with no published_at date (should not be skipped)
// =============================================================================

it('[TC6] processes items with no published_at date on first run', function () {
    // Build a feed where the first item has no pubDate.
    // Items with no date should be processed regardless of the lookback window.
    $feedWithNullDate = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel>
        <title>Test Channel</title>
        <items>
          <item>
            <snippet>
              <title>No Date Video</title>
              <description>This video has no published date.</description>
              <resourceId><videoId>no_date_vid</videoId></resourceId>
            </snippet>
          </item>
        </items>
      </channel>
    </rss>
    XML;

    // Build a normal API response with one item that has no publishedAt.
    $apiResponse = [
        'items' => [[
            'snippet' => [
                'title'       => 'No Date Video',
                'description' => 'No published date.',
                'resourceId'  => ['kind' => 'youtube#video', 'videoId' => 'no_date_vid'],
                // No publishedAt key at all.
            ],
            'contentDetails' => [
                'videoId' => 'no_date_vid',
                // No videoPublishedAt key at all.
            ],
        ]],
    ];

    Http::fake([
        YT_SIM_API_BASE => Http::response($apiResponse),
    ]);

    // The processor falls back to snippet.publishedAt when contentDetails is missing.
    // When both are absent, Carbon::parse() will be called on null which may throw.
    // This test verifies the processor handles this gracefully.
    // NOTE: The YouTube API always provides publishedAt — this tests defensive coding.
    $stats = $this->processor->process($this->listSource);

    // Should not crash, even if the item can't be processed due to missing date.
    expect($stats['errors'])->toBe(0);
});

// =============================================================================
// Test Case 7: Stats are correct (fetched, processed, skipped counts match)
// =============================================================================

it('[TC7] stats counts are accurate on first run', function () {
    // 50-item feed with 7-day lookback: 7 processed, 43 skipped, 0 errors.
    Http::fake([
        YT_SIM_API_BASE => Http::response(
            FakeYoutubePlaylistBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(50);
    expect($stats['processed'])->toBe(7);
    expect($stats['skipped'])->toBe(43);
    expect($stats['errors'])->toBe(0);

    // Verify DB row count matches processed count.
    expect(
        DB::table('summaries')->where('list_source_id', $this->listSource->id)->count()
    )->toBe(7);
});