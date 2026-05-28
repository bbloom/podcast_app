<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SimulateTextBasedRssRegularRunProcessingTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\ArticleExtractorService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\TextBasedRss\Services\TextBasedRssContentProcessor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeTextBasedRssFeedBuilder;
use Tests\TestCase;

class SimulateTextBasedRssRegularRunProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User                         $user;
    private ListModel                    $list;
    private TextBasedRssFeed             $feed;
    private object                       $listSource;
    private LlmService                   $llmMock;
    private ArticleExtractorService      $extractorMock;
    private TextBasedRssContentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    // =========================================================================
    // TC8
    // =========================================================================

    #[Test]
    public function TC8_routes_to_regular_run_when_bookmark_exists(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => FakeTextBasedRssFeedBuilder::sourceUrl(7),
            'processed_at'   => now()->subDays(7),
        ]);

        Http::fake([
            'news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(6, $stats['processed']);
    }

    // =========================================================================
    // TC9–11
    // =========================================================================

    #[Test]
    public function TC9_11_stops_at_bookmark_immediately_zero_processed_stats_all_zero(): void
    {
        $bookmarkUrl = FakeTextBasedRssFeedBuilder::sourceUrl(1);

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([
            'news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame($bookmarkUrl, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC12
    // =========================================================================

    #[Test]
    public function TC12_second_zero_result_run_is_identical_to_first(): void
    {
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

        $this->assertSame($stats1, $stats2);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC13–18
    // =========================================================================

    #[Test]
    public function TC13_18_processes_exactly_5_new_items_when_prepended_stops_at_bookmark_rotates(): void
    {
        $originalBookmarkUrl = FakeTextBasedRssFeedBuilder::sourceUrl(1);

        // Bookmark is 30 days old so all 5 new items (spaced 1 day apart
        // starting from now()) are clearly newer than processed_at.
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $originalBookmarkUrl,
            'processed_at'   => now()->subDays(30),
        ]);

        // New items start at now() — all 5 are within the last 5 days.
        // Original items start at 31 days ago — all clearly older than bookmark.
        $newXml      = FakeTextBasedRssFeedBuilder::build(5,  now(),              1, 'new-art');
        $originalXml = FakeTextBasedRssFeedBuilder::build(50, now()->subDays(31), 1, 'article');

        preg_match_all('/<item>.*?<\/item>/s', $newXml,      $newItems);
        preg_match_all('/<item>.*?<\/item>/s', $originalXml, $oldItems);

        $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0"><channel><title>Test Feed</title>'
            . implode("\n", array_merge($newItems[0], $oldItems[0]))
            . '</channel></rss>';

        Http::fake(['news.example.com/feed.xml' => Http::response($combinedXml)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(5, $stats['processed']);
        $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

        $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'), $newBookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
        $this->assertSame(0, $stats['errors']);
    }

    // =========================================================================
    // TC19–21
    // =========================================================================

    #[Test]
    public function TC19_21_fallback_stop_when_bookmarked_article_is_gone_from_feed(): void
    {
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

        $this->assertSame(3, $stats['processed']);

        $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'), $newBookmark->source_url);
    }

    // =========================================================================
    // TC27
    // =========================================================================

    #[Test]
    public function TC27_single_item_feed_first_run_processes_it_regular_run_stops_immediately(): void
    {
        Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(1, now(), 1))]);
        $firstStats = $this->processor->process($this->listSource);
        $this->assertSame(1, $firstStats['processed']);

        Http::fake(['news.example.com/feed.xml' => Http::response(FakeTextBasedRssFeedBuilder::build(1, now(), 1))]);
        $regularStats = $this->processor->process($this->listSource);
        $this->assertSame(0, $regularStats['processed']);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC28
    // =========================================================================

    #[Test]
    public function TC28_two_list_sources_on_same_feed_have_independent_bookmarks(): void
    {
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

        $this->assertSame(2, ContentAlreadyProcessed::count());
        $this->assertSame($this->listSource->id, ContentAlreadyProcessed::findBookmark($this->listSource->id)->list_source_id);
        $this->assertSame($listSource2->id, ContentAlreadyProcessed::findBookmark($listSource2->id)->list_source_id);
    }

    // =========================================================================
    // TC30–34
    // =========================================================================

    #[Test]
    public function TC30_34_full_end_to_end_chain_reproduces_and_verifies_the_bug_fix(): void
    {
        $feed50Xml = FakeTextBasedRssFeedBuilder::build(50, now(), 1);

        // New items built 30 days in the future — unambiguously newer than the
        // Run 1 bookmark (processed_at ≈ now()).
        $newXml = FakeTextBasedRssFeedBuilder::build(5, now()->addDays(30), 1, 'new-art');

        preg_match_all('/<item>.*?<\/item>/s', $newXml,    $newItems);
        preg_match_all('/<item>.*?<\/item>/s', $feed50Xml, $oldItems);

        $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0"><channel><title>Test Feed</title>'
            . implode("\n", array_merge($newItems[0], $oldItems[0]))
            . '</channel></rss>';

        // Set up the full sequence upfront — each run gets the next response
        // in order. Using Http::sequence() avoids Http::fake() stacking issues
        // that arise when it is called multiple times in the same test method.
        Http::fake([
            'news.example.com/feed.xml' => Http::sequence()
                ->push($feed50Xml)   // Run 1
                ->push($feed50Xml)   // Run 2
                ->push($feed50Xml)   // Run 3
                ->push($combinedXml) // Run 4
                ->push($combinedXml) // Run 5
        ]);

        // Run 1
        $run1 = $this->processor->process($this->listSource);

        $this->assertSame(7,  $run1['processed'], 'Run 1: should process 7 within lookback');
        $this->assertSame(43, $run1['skipped'],   'Run 1: should skip 43 outside lookback');
        $this->assertNotNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));

        $bookmarkAfterRun1 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;

        // Run 2: THE CRITICAL CHECK
        $run2 = $this->processor->process($this->listSource);

        $this->assertSame(0, $run2['processed'], 'Run 2: THE BUG CHECK — must process 0, not 43');
        $this->assertSame($bookmarkAfterRun1, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);

        // Run 3
        $run3 = $this->processor->process($this->listSource);

        $this->assertSame(0, $run3['processed'], 'Run 3: should still process 0');

        // Run 4
        $run4 = $this->processor->process($this->listSource);

        $this->assertSame(5, $run4['processed'], 'Run 4: should process exactly 5 new articles');

        $bookmarkAfterRun4 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
        $this->assertNotSame($bookmarkAfterRun1, $bookmarkAfterRun4, 'Run 4: bookmark must have rotated');
        $this->assertSame(FakeTextBasedRssFeedBuilder::sourceUrl(1, 'new-art'), $bookmarkAfterRun4);

        // Run 5
        $run5 = $this->processor->process($this->listSource);

        $this->assertSame(0, $run5['processed'], 'Run 5: should process 0 after new items absorbed');
        $this->assertSame($bookmarkAfterRun4, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }
}