<?php

// tests/Feature/Processing/simulate_textbasedrss_regular_run_processing.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\ArticleExtractorService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\TextBasedRss\Services\TextBasedRssContentProcessor;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeTextBasedRssFeedBuilder;

/**
 * Simulation: Text-Based RSS Regular Run Processing
 *
 * Covers test cases 8–34 from README_PROCESSING_TEST_CASES.md.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['processing.first_run_lookback_days' => 7]);

    $this->user = User::factory()->create();
    $this->list = ListModel::factory()->forUser($this->user)->create();
    $this->feed = TextBasedRssFeed::factory()->forUser($this->user)->create([
        'rss_url' => 'https://news.example.com/feed.xml',
    ]);

    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => $this->feed->id,
        'sourceable_type' => 'text_based_rss_feed',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->listSource = DB::table('list_sources')
        ->where('sourceable_id', $this->feed->id)
        ->first();

    $this->llmMock       = mock(LlmService::class);
    $this->extractorMock = mock(ArticleExtractorService::class);
    app()->instance(LlmService::class, $this->llmMock);
    app()->instance(ArticleExtractorService::class, $this->extractorMock);

    $this->processor = new TextBasedRssContentProcessor($this->llmMock, $this->extractorMock);
});

// =============================================================================
// TC8: Routes to regularRunProcessing() when bookmark exists
// =============================================================================

it('[TC8] routes to regular run when bookmark exists', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => FakeTextBasedRssFeedBuilder::sourceUrl(7),
        'processed_at'   => now()->subDays(7),
    ]);

    Http::fake([
        'news.example.com/feed.xml' => Http::response(
            FakeTextBasedRssFeedBuilder::build(50, now(), 1)
        ),
    ]);

    $stats = $this->processor->process($this->listSource);

    // Stops at item 7, processes items 1–6.
    expect($stats['processed'])->toBe(6);
});

// =============================================================================
// TC9–11: Zero-result regular run
// =============================================================================

it('[TC9-11] stops at bookmark immediately, zero processed, stats all zero', function () {
    $bookmarkUrl = FakeTextBasedRssFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake([
        'news.example.com/feed.xml' => Http::response(
            FakeTextBasedRssFeedBuilder::build(50, now(), 1)
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
    $bookmarkUrl = FakeTextBasedRssFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $bookmarkUrl,
        'processed_at'   => now()->subHour(),
    ]);

    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1))]);
    $stats1 = $this->processor->process($this->listSource);

    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1))]);
    $stats2 = $this->processor->process($this->listSource);

    expect($stats1)->toBe($stats2);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// TC13–18: 5 new items prepended
// =============================================================================

it('[TC13-18] processes exactly 5 new items when prepended, stops at bookmark, rotates', function () {
    $originalBookmarkUrl = FakeTextBasedRssFeedBuilder::sourceUrl(1);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => $originalBookmarkUrl,
        'processed_at'   => now()->subDay(),
    ]);

    // Build combined feed: 5 new articles (published tomorrow) + original 50.
    $newXml      = FakeTextBasedRssFeedBuilder::build(5,  now()->addDay(), 1, 'new-art');
    $originalXml = FakeTextBasedRssFeedBuilder::build(50, now()->subDay(), 1, 'article');

    preg_match_all('/<item>.*?<\/item>/s', $newXml,      $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $originalXml, $oldItems);

    $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<rss version="2.0"><channel><title>Test Feed</title>'
        . implode("\n", array_merge($newItems[0], $oldItems[0]))
        . '</channel></rss>';

    Http::fake(['news.example.com/feed.xml' => Http::response($combinedXml)]);

    $stats = $this->processor->process($this->listSource);

    // TC13: exactly 5 new items processed.
    expect($stats['processed'])->toBe(5);

    // TC14 + TC15: stopped at bookmark, bookmark item not processed.
    $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

    // TC17: bookmark rotated to newest new item.
    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($newBookmark->source_url)->toBe(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'));
    expect(ContentAlreadyProcessed::count())->toBe(1);

    expect($stats['errors'])->toBe(0);
});

// =============================================================================
// TC19–21: Bookmark URL gone from feed (fallback stop)
// =============================================================================

it('[TC19-21] fallback stop when bookmarked article is gone from feed', function () {
    $bookmarkTime = now()->subDays(3);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://news.example.com/articles/deleted-article',
        'processed_at'   => $bookmarkTime,
    ]);

    $newXml = FakeTextBasedRssFeedBuilder::build(3,  now(),                   1, 'new-art');
    $oldXml = FakeTextBasedRssFeedBuilder::build(50, $bookmarkTime->subDay(), 1, 'old-art');

    preg_match_all('/<item>.*?<\/item>/s', $newXml, $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $oldXml, $oldItems);

    $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<rss version="2.0"><channel><title>Test Feed</title>'
        . implode("\n", array_merge($newItems[0], $oldItems[0]))
        . '</channel></rss>';

    Http::fake(['news.example.com/feed.xml' => Http::response($combinedXml)]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(3);

    $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($newBookmark->source_url)->toBe(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'));
});

// =============================================================================
// TC27: Single item feed
// =============================================================================

it('[TC27] single item feed — first run processes it, regular run stops immediately', function () {
    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(1, now(), 1))]);
    $firstStats = $this->processor->process($this->listSource);
    expect($firstStats['processed'])->toBe(1);

    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(1, now(), 1))]);
    $regularStats = $this->processor->process($this->listSource);
    expect($regularStats['processed'])->toBe(0);
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// TC28: Two list_sources on same feed have independent bookmarks
// =============================================================================

it('[TC28] two list_sources on same feed have independent bookmarks', function () {
    $list2 = ListModel::factory()->forUser($this->user)->create();

    DB::table('list_sources')->insert([
        'list_id'         => $list2->id,
        'sourceable_id'   => $this->feed->id,
        'sourceable_type' => 'text_based_rss_feed',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $listSource2 = DB::table('list_sources')->where('list_id', $list2->id)->first();

    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1))]);
    $this->processor->process($this->listSource);

    Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1))]);
    $this->processor->process($listSource2);

    expect(ContentAlreadyProcessed::count())->toBe(2);
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->list_source_id)->toBe($this->listSource->id);
    expect(ContentAlreadyProcessed::findBookmark($listSource2->id)->list_source_id)->toBe($listSource2->id);
});

// =============================================================================
// TC30–34: Full end-to-end chain
// =============================================================================

it('[TC30-34] full end-to-end chain reproduces and verifies the bug fix', function () {
    $feed50Xml = FakeTextBasedRssFeedBuilder::build(50, now(), 1);

    // ── Run 1: First run ──────────────────────────────────────────────────────
    Http::fake(['news.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run1 = $this->processor->process($this->listSource);

    expect($run1['processed'])->toBe(7, 'Run 1: should process 7 within lookback');
    expect($run1['skipped'])->toBe(43, 'Run 1: should skip 43 outside lookback');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->not->toBeNull();

    $bookmarkAfterRun1 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;

    // ── Run 2: Same feed — THE CRITICAL CHECK ────────────────────────────────
    Http::fake(['news.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run2 = $this->processor->process($this->listSource);

    expect($run2['processed'])->toBe(0, 'Run 2: THE BUG CHECK — must process 0, not 43');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkAfterRun1);

    // ── Run 3: Same feed again ────────────────────────────────────────────────
    Http::fake(['news.example.com/feed.xml' => Http::response($feed50Xml)]);
    $run3 = $this->processor->process($this->listSource);

    expect($run3['processed'])->toBe(0, 'Run 3: should still process 0');

    // ── Run 4: 5 new articles prepended ──────────────────────────────────────
    $newXml = FakeTextBasedRssFeedBuilder::build(5, now()->addDay(), 1, 'new-art');

    preg_match_all('/<item>.*?<\/item>/s', $newXml,    $newItems);
    preg_match_all('/<item>.*?<\/item>/s', $feed50Xml, $oldItems);

    $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<rss version="2.0"><channel><title>Test Feed</title>'
        . implode("\n", array_merge($newItems[0], $oldItems[0]))
        . '</channel></rss>';

    Http::fake(['news.example.com/feed.xml' => Http::response($combinedXml)]);
    $run4 = $this->processor->process($this->listSource);

    expect($run4['processed'])->toBe(5, 'Run 4: should process exactly 5 new articles');

    $bookmarkAfterRun4 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
    expect($bookmarkAfterRun4)->not->toBe($bookmarkAfterRun1, 'Run 4: bookmark must have rotated');
    expect($bookmarkAfterRun4)->toBe(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'));

    // ── Run 5: Same feed as run 4, nothing new ────────────────────────────────
    Http::fake(['news.example.com/feed.xml' => Http::response($combinedXml)]);
    $run5 = $this->processor->process($this->listSource);

    expect($run5['processed'])->toBe(0, 'Run 5: should process 0 after new items absorbed');
    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url)->toBe($bookmarkAfterRun4);
});