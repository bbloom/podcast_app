<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SimulatePodcastFirstRunProcessingTest.php

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

class SimulatePodcastFirstRunProcessingTest extends TestCase
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
    // TC1: Routes to firstRunProcessing() when no bookmark exists
    // =========================================================================

    #[Test]
    public function TC1_routes_to_first_run_when_no_bookmark_exists(): void
    {
        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));

        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(7, $stats['processed']);
        $this->assertSame(43, $stats['skipped']);
    }

    // =========================================================================
    // TC2: Processes only items within the lookback window (7 of 50)
    // =========================================================================

    #[Test]
    public function TC2_processes_only_items_within_the_lookback_window_on_first_run(): void
    {
        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(50, $stats['fetched']);
        $this->assertSame(7,  $stats['processed']);
        $this->assertSame(0,  $stats['errors']);
        $this->assertSame(7, DB::table('summaries')->where('list_source_id', $this->listSource->id)->count());
    }

    // =========================================================================
    // TC3: Skips items older than the lookback window (43 of 50)
    // =========================================================================

    #[Test]
    public function TC3_skips_items_older_than_the_lookback_window_on_first_run(): void
    {
        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(43, $stats['skipped']);
        $this->assertDatabaseMissing('summaries', ['source_url' => FakePodcastFeedBuilder::sourceUrl(8)]);
        $this->assertDatabaseMissing('summaries', ['source_url' => FakePodcastFeedBuilder::sourceUrl(50)]);
    }

    // =========================================================================
    // TC4: Inserts bookmark pointing to the newest processed item
    // =========================================================================

    #[Test]
    public function TC4_inserts_bookmark_pointing_to_the_newest_processed_item_after_first_run(): void
    {
        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertNotNull($bookmark);
        $this->assertSame(FakePodcastFeedBuilder::sourceUrl(1), $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC5: Inserts NO bookmark when all items are older than the lookback window
    // =========================================================================

    #[Test]
    public function TC5_inserts_no_bookmark_when_no_items_fall_within_the_lookback_window(): void
    {
        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(
                FakePodcastFeedBuilder::build(50, now()->subDays(10), 1)
            ),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0,  $stats['processed']);
        $this->assertSame(50, $stats['skipped']);
        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));
    }

    // =========================================================================
    // TC6: Processes items with no published_at date
    // =========================================================================

    #[Test]
    public function TC6_processes_items_with_no_published_at_date_on_first_run(): void
    {
        $feedNoPubDate = <<<XML
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
            'podcast.example.com/feed.xml' => Http::response($feedNoPubDate),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://podcast.example.com/episodes/no-date']);
    }

    // =========================================================================
    // TC7: Stats are correct
    // =========================================================================

    #[Test]
    public function TC7_stats_counts_are_accurate_on_first_run(): void
    {
        Http::fake([
            'podcast.example.com/feed.xml' => Http::response(FakePodcastFeedBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(50, $stats['fetched']);
        $this->assertSame(7,  $stats['processed']);
        $this->assertSame(43, $stats['skipped']);
        $this->assertSame(0,  $stats['errors']);
        $this->assertSame(7, DB::table('summaries')->where('list_source_id', $this->listSource->id)->count());
    }
}