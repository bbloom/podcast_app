<?php

// tests/Feature/Processing/simulate_podcast_first_run_processing.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Podcasts\Services\PodcastContentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakePodcastFeedBuilder;

/**
 * Simulation: Podcast First Run Processing
 *
 * Tests all first-run scenarios using a 50-item fake RSS feed with items
 * spaced 1 day apart. Lookback window is set to 7 days.
 *
 * Covers test cases 1–7 from README_PROCESSING_TEST_CASES.md.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['processing.first_run_lookback_days' => 7]);

    $this->user    = User::factory()->create();
    $this->list    = ListModel::factory()->forUser($this->user)->create();
    $this->podcast = Podcast::factory()->forUser($this->user)->create([
        'rss_url' => 'https://podcast.example.com/feed.xml',
    ]);

    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => $this->podcast->id,
        'sourceable_type' => 'podcast',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->listSource = DB::table('list_sources')
        ->where('sourceable_id', $this->podcast->id)
        ->first();

    $this->llmMock = mock(LlmService::class);
    app()->instance(LlmService::class, $this->llmMock);
    $this->processor = new PodcastContentProcessor($this->llmMock);
});

// =============================================================================
// Test Case 1: Routes to firstRunProcessing() when no bookmark exists
// =============================================================================

it('[TC1] routes to first run when no bookmark exists', function () {
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();

    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    // Lookback window applied — confirms first run path was taken.
    expect($stats['processed'])->toBe(7);
    expect($stats['skipped'])->toBe(43);
});

// =============================================================================
// Test Case 2: Processes only items within the lookback window (7 of 50)
// =============================================================================

it('[TC2] processes only items within the lookback window on first run', function () {
    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(50);
    expect($stats['processed'])->toBe(7);
    expect($stats['errors'])->toBe(0);
    expect(DB::table('summaries')->where('list_source_id', $this->listSource->id)->count())->toBe(7);
});

// =============================================================================
// Test Case 3: Skips items older than the lookback window (43 of 50)
// =============================================================================

it('[TC3] skips items older than the lookback window on first run', function () {
    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['skipped'])->toBe(43);
    $this->assertDatabaseMissing('summaries', ['source_url' => FakePodcastFeedBuilder::sourceUrl(8)]);
    $this->assertDatabaseMissing('summaries', ['source_url' => FakePodcastFeedBuilder::sourceUrl(50)]);
});

// =============================================================================
// Test Case 4: Inserts bookmark pointing to the newest processed item
// =============================================================================

it('[TC4] inserts bookmark pointing to the newest processed item after first run', function () {
    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark)->not->toBeNull();
    expect($bookmark->source_url)->toBe(FakePodcastFeedBuilder::sourceUrl(1));
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// Test Case 5: Inserts NO bookmark when all items are older than the lookback window
// =============================================================================

it('[TC5] inserts no bookmark when no items fall within the lookback window', function () {
    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now()->subDays(10), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    expect($stats['skipped'])->toBe(50);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();
});

// =============================================================================
// Test Case 6: Processes items with no published_at date
// =============================================================================

it('[TC6] processes items with no published_at date on first run', function () {
    $feedWithNullDate = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel>
        <title>Test Podcast</title>
        <item>
          <title>No Date Episode</title>
          <link>https://podcast.example.com/episodes/no-date</link>
          <guid isPermaLink="false">https://podcast.example.com/episodes/no-date</guid>
          <description>This episode has no pubDate.</description>
        </item>
      </channel>
    </rss>
    XML;

    Http::fake([
        'podcast.example.com/feed.xml' => Http::response($feedWithNullDate),
    ]);

    $stats = $this->processor->process($this->listSource);

    // Item with no date should be processed — cannot determine age.
    expect($stats['processed'])->toBe(1);
    expect($stats['errors'])->toBe(0);
    $this->assertDatabaseHas('summaries', [
        'source_url' => 'https://podcast.example.com/episodes/no-date',
    ]);
});

// =============================================================================
// Test Case 7: Stats are correct
// =============================================================================

it('[TC7] stats counts are accurate on first run', function () {
    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(50);
    expect($stats['processed'])->toBe(7);
    expect($stats['skipped'])->toBe(43);
    expect($stats['errors'])->toBe(0);
    expect(DB::table('summaries')->where('list_source_id', $this->listSource->id)->count())->toBe(7);
});