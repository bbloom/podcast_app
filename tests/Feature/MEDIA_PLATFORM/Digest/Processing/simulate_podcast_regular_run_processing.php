<?php

// tests/Feature/Processing/simulate_podcast_regular_run_processing.php

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
 * Simulation: Podcast Regular Run Processing
 *
 * Covers test cases 8–34 from README_PROCESSING_TEST_CASES.md.
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
// TC8: Routes to regularRunProcessing() when bookmark exists
// =============================================================================

it('[TC8] routes to regular run when bookmark exists', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => FakePodcastFeedBuilder::sourceUrl(7),
        'processed_at'   => now()->subDays(7),
    ]);

    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    // Regular run stops at item 7 — processes items 1–6.
    expect($stats['processed'])->toBe(6);
});

// =============================================================================
// TC9–11: Zero-result regular run
// =============================================================================

it('[TC9-11] stops at bookmark immediately, processes zero items, stats all zero', function () {
    $bookmarkUrl = FakePodcastFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake([
        'podcast.example.com/feed.xml' => Http::response(
            FakePodcastFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    expect($stats['skipped'])->toBe(0);
    expect($stats['errors'])->toBe(0);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkUrl);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// TC12: Idempotency
// =============================================================================

it('[TC12] second zero-result run is identical to first', function () {
    $bookmarkUrl = FakePodcastFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1))]);
    $stats1 = $this->processor->process($this->listSource);

    Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1))]);
    $stats2 = $this->processor->process($this->listSource);

    expect($stats1)->toBe($stats2);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// TC13–18: 5 new items prepended
// =============================================================================

it('[TC13-18] processes exactly 5 new items when prepended, stops at bookmark, rotates', function () {
    $originalBookmarkUrl = FakePodcastFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $originalBookmarkUrl,
        'processed_at'   => now()->subDay(),
    ]);

    // Build combined feed: 5 new episodes + original 50.
    $newXml      = FakePodcastFeedBuilder::build(5,  now(),           1, 'new-ep');
    $originalXml = FakePodcastFeedBuilder::build(50, now()->subDay(), 1, 'episode');

    // Merge XML items from both feeds into one channel.
    $combinedXml = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0"
        xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
        xmlns:content="http://purl.org/rss/1.0/modules/content/">
      <channel>
        <title>Fake Test Podcast</title>
        <link>https://podcast.example.com</link>
        <description>Combined feed.</description>
        XML;

    // Extract <item> blocks from both feeds and merge them.
    preg_match_all('/<item>.*?<\/item>/s', $newXml,      $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $originalXml, $originalItems);

    $allItems    = array_merge($newItems[0], $originalItems[0]);
    $combinedXml .= implode("\n", $allItems);
    $combinedXml .= '</channel></rss>';

    Http::fake(['podcast.example.com/feed.xml' => Http::response($combinedXml)]);

    $stats = $this->processor->process($this->listSource);

    // TC13: exactly 5 new items processed.
    expect($stats['processed'])->toBe(5);

    // TC14 + TC15: stopped at bookmark, bookmark item not processed.
    $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

    // TC17: bookmark rotated to newest new item.
    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($newBookmark->source_url)->toBe(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'));
    expect(ContentAlreadyProcessed::count())->toBe(1);

    // TC18: stats confirm exactly 5.
    expect($stats['errors'])->toBe(0);
});

// =============================================================================
// TC19–21: Bookmark URL gone from feed (fallback stop)
// =============================================================================

it('[TC19-21] fallback stop when bookmarked episode is gone from feed', function () {
    $bookmarkTime = now()->subDays(3);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://podcast.example.com/episodes/deleted-episode',
        'processed_at'   => $bookmarkTime,
    ]);

    // 3 new episodes + 50 old episodes (all older than bookmark).
    $newXml = FakePodcastFeedBuilder::build(3,  now(),                   1, 'new-ep');
    $oldXml = FakePodcastFeedBuilder::build(50, $bookmarkTime->subDay(), 1, 'old-ep');

    preg_match_all('/<item>.*?<\/item>/s', $newXml, $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $oldXml, $oldItems);

    $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'
        . '<channel><title>Test</title>'
        . implode("\n", array_merge($newItems[0], $oldItems[0]))
        . '</channel></rss>';

    Http::fake(['podcast.example.com/feed.xml' => Http::response($combinedXml)]);

    $stats = $this->processor->process($this->listSource);

    // TC19 + TC20: fallback stop triggered, 3 new items processed.
    expect($stats['processed'])->toBe(3);

    // TC21: bookmark rotated to newest new item.
    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($newBookmark->source_url)->toBe(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'));
});

// =============================================================================
// TC27: Single item feed
// =============================================================================

it('[TC27] single item feed — first run processes it, regular run stops immediately', function () {
    Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(1, now(), 1))]);
    $firstStats = $this->processor->process($this->listSource);
    expect($firstStats['processed'])->toBe(1);

    Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(1, now(), 1))]);
    $regularStats = $this->processor->process($this->listSource);
    expect($regularStats['processed'])->toBe(0);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// TC30–34: Full end-to-end chain
// =============================================================================

it('[TC30-34] full end-to-end chain reproduces and verifies the bug fix', function () {
    $feed50Xml = FakePodcastFeedBuilder::build(50, now(), 1);

    // ── Run 1: First run ──────────────────────────────────────────────────────
    Http::fake(['podcast.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run1 = $this->processor->process($this->listSource);

    expect($run1['processed'])->toBe(7, 'Run 1: should process 7 within lookback');
    expect($run1['skipped'])->toBe(43, 'Run 1: should skip 43 outside lookback');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->not->toBeNull();

    $bookmarkAfterRun1 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;

    // ── Run 2: Same feed — THE CRITICAL CHECK ────────────────────────────────
    Http::fake(['podcast.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run2 = $this->processor->process($this->listSource);

    expect($run2['processed'])->toBe(0, 'Run 2: THE BUG CHECK — must process 0, not 43');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkAfterRun1);

    // ── Run 3: Same feed again ────────────────────────────────────────────────
    Http::fake(['podcast.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run3 = $this->processor->process($this->listSource);

    expect($run3['processed'])->toBe(0, 'Run 3: should still process 0');

    // ── Run 4: 5 new episodes prepended ──────────────────────────────────────
    $newXml = FakePodcastFeedBuilder::build(5, now()->addDay(), 1, 'new-ep');

    preg_match_all('/<item>.*?<\/item>/s', $newXml,    $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $feed50Xml, $oldItems);

    $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'
        . '<channel><title>Test</title>'
        . implode("\n", array_merge($newItems[0], $oldItems[0]))
        . '</channel></rss>';

    Http::fake(['podcast.example.com/feed.xml' => Http::response($combinedXml)]);
    $run4 = $this->processor->process($this->listSource);

    expect($run4['processed'])->toBe(5, 'Run 4: should process exactly 5 new episodes');

    $bookmarkAfterRun4 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
    expect($bookmarkAfterRun4)->not->toBe($bookmarkAfterRun1, 'Run 4: bookmark must have rotated');
    expect($bookmarkAfterRun4)->toBe(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'));

    // ── Run 5: Same feed as run 4, nothing new ────────────────────────────────
    Http::fake(['podcast.example.com/feed.xml' => Http::response($combinedXml)]);
    $run5 = $this->processor->process($this->listSource);

    expect($run5['processed'])->toBe(0, 'Run 5: should process 0 after new items absorbed');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkAfterRun4);
});