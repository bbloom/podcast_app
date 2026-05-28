<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SimulatePodcastRegularRunProcessingTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Podcasts\Services\PodcastContentProcessor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakePodcastFeedBuilder;
use Tests\TestCase;

class SimulatePodcastRegularRunProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User                    $user;
    private ListModel               $list;
    private Podcast                 $podcast;
    private object                  $listSource;
    private LlmService              $llmMock;
    private PodcastContentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

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
            'source_url'     => FakePodcastFeedBuilder::sourceUrl(7),
            'processed_at'   => now()->subDays(7),
        ]);

        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(6, $stats['processed']);
    }

    // =========================================================================
    // TC9–11
    // =========================================================================

    #[Test]
    public function TC9_11_stops_at_bookmark_immediately_processes_zero_items_stats_all_zero(): void
    {
        $bookmarkUrl = FakePodcastFeedBuilder::sourceUrl(1);

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
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

        $this->assertSame($stats1, $stats2);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC13–18
    // =========================================================================

    #[Test]
    public function TC13_18_processes_exactly_5_new_items_when_prepended_stops_at_bookmark_rotates(): void
    {
        $originalBookmarkUrl = FakePodcastFeedBuilder::sourceUrl(1);

        // Bookmark is 30 days old so all 5 new items (spaced 1 day apart
        // starting from now()) are clearly newer than processed_at.
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $originalBookmarkUrl,
            'processed_at'   => now()->subDays(30),
        ]);

        // New items start at now() — all 5 within last 5 days.
        // Old items start at 31 days ago — all clearly older than bookmark.
        $newXml      = FakePodcastFeedBuilder::build(5,  now(),              1, 'new-ep');
        $originalXml = FakePodcastFeedBuilder::build(50, now()->subDays(31), 1, 'episode');

        preg_match_all('/<item>.*?<\/item>/s', $newXml,      $newItems);
        preg_match_all('/<item>.*?<\/item>/s', $originalXml, $originalItems);

        $allItems    = array_merge($newItems[0], $originalItems[0]);
        $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:content="http://purl.org/rss/1.0/modules/content/">'
            . '<channel><title>Fake Test Podcast</title><link>https://podcast.example.com</link><description>Combined feed.</description>'
            . implode("\n", $allItems)
            . '</channel></rss>';

        Http::fake(['podcast.example.com/feed.xml' => Http::response($combinedXml)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(5, $stats['processed']);
        $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

        $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'), $newBookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
        $this->assertSame(0, $stats['errors']);
    }

    // =========================================================================
    // TC19–21
    // =========================================================================

    #[Test]
    public function TC19_21_fallback_stop_when_bookmarked_episode_is_gone_from_feed(): void
    {
        $bookmarkTime = now()->subDays(3);

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://podcast.example.com/episodes/deleted-episode',
            'processed_at'   => $bookmarkTime,
        ]);

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

        $this->assertSame(3, $stats['processed']);

        $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'), $newBookmark->source_url);
    }

    // =========================================================================
    // TC27
    // =========================================================================

    #[Test]
    public function TC27_single_item_feed_first_run_processes_it_regular_run_stops_immediately(): void
    {
        Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(1, now(), 1))]);
        $firstStats = $this->processor->process($this->listSource);
        $this->assertSame(1, $firstStats['processed']);

        Http::fake(['podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(1, now(), 1))]);
        $regularStats = $this->processor->process($this->listSource);
        $this->assertSame(0, $regularStats['processed']);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC30–34
    // =========================================================================

    #[Test]
    public function TC30_34_full_end_to_end_chain_reproduces_and_verifies_the_bug_fix(): void
    {
        $feed50Xml = FakePodcastFeedBuilder::build(50, now(), 1);

        // New items built 30 days in the future — unambiguously newer than the
        // Run 1 bookmark (processed_at ≈ now()).
        $newXml = FakePodcastFeedBuilder::build(5, now()->addDays(30), 1, 'new-ep');

        preg_match_all('/<item>.*?<\/item>/s', $newXml,    $newItems);
        preg_match_all('/<item>.*?<\/item>/s', $feed50Xml, $oldItems);

        $combinedXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'
            . '<channel><title>Test</title>'
            . implode("\n", array_merge($newItems[0], $oldItems[0]))
            . '</channel></rss>';

        // Set up the full sequence upfront — each run gets the next response
        // in order, avoiding Http::fake() stacking issues.
        Http::fake([
            'podcast.example.com/feed.xml' => Http::sequence()
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

        $this->assertSame(5, $run4['processed'], 'Run 4: should process exactly 5 new episodes');

        $bookmarkAfterRun4 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
        $this->assertNotSame($bookmarkAfterRun1, $bookmarkAfterRun4, 'Run 4: bookmark must have rotated');
        $this->assertSame(FakePodcastFeedBuilder::sourceUrl(1, 'new-ep'), $bookmarkAfterRun4);

        // Run 5
        $run5 = $this->processor->process($this->listSource);

        $this->assertSame(0, $run5['processed'], 'Run 5: should process 0 after new items absorbed');
        $this->assertSame($bookmarkAfterRun4, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }
}