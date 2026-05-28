<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/PodcastContentProcessorTest.php

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
use Tests\TestCase;

class PodcastContentProcessorTest extends TestCase
{
    use RefreshDatabase;

    private const FEED_URL      = 'https://feeds.example.com/podcast.xml';
    private const TRANSCRIPT_URL = 'https://transcripts.example.com/ep1.srt';

    private User                   $user;
    private ListModel              $list;
    private Podcast                $podcast;
    private object                 $listSource;
    private LlmService             $llmMock;
    private PodcastContentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->list    = ListModel::factory()->forUser($this->user)->create();
        $this->podcast = Podcast::factory()->forUser($this->user)->create([
            'rss_url' => self::FEED_URL,
            'title'   => 'Test Podcast',
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
            ->where('sourceable_type', 'podcast')
            ->first();

        $this->llmMock = mock(LlmService::class);
        app()->instance(LlmService::class, $this->llmMock);
        $this->processor = new PodcastContentProcessor($this->llmMock);
    }

    // =========================================================================
    // Helpers & Fixtures
    // =========================================================================

    private function setPodcastProcessingMode(string $mode, ?string $searchTerms = null): void
    {
        DB::table('list_sources')
            ->where('id', $this->listSource->id)
            ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
        $this->listSource = DB::table('list_sources')->find($this->listSource->id);
    }

    private function podcastFeedTwoItems(
        string $guid1    = 'https://example.com/ep1',
        string $title1   = 'Episode One Title',
        string $desc1    = 'Short teaser for episode one.',
        string $encoded1 = '<p>Full show notes for episode one.</p>',
        string $pubDate1 = '',
        string $guid2    = 'https://example.com/ep2',
        string $title2   = 'Episode Two Title',
        string $desc2    = 'Short teaser for episode two.',
        string $pubDate2 = '',
    ): string {
        $pubDate1 = $pubDate1 ?: now()->subHour()->toRfc7231String();
        $pubDate2 = $pubDate2 ?: now()->subHours(2)->toRfc7231String();

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"
            xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
            xmlns:content="http://purl.org/rss/1.0/modules/content/">
          <channel>
            <title>Test Podcast</title>
            <item>
              <title>{$title1}</title>
              <link>{$guid1}</link>
              <guid isPermaLink="false">{$guid1}</guid>
              <description>{$desc1}</description>
              <content:encoded><![CDATA[{$encoded1}]]></content:encoded>
              <pubDate>{$pubDate1}</pubDate>
            </item>
            <item>
              <title>{$title2}</title>
              <link>{$guid2}</link>
              <guid isPermaLink="false">{$guid2}</guid>
              <description>{$desc2}</description>
              <pubDate>{$pubDate2}</pubDate>
            </item>
          </channel>
        </rss>
        XML;
    }

    private function podcastFeedWithTranscript(
        string $guid          = 'https://example.com/ep1',
        string $title         = 'Episode With Transcript',
        string $desc          = 'Short teaser.',
        string $transcriptUrl = 'https://transcripts.example.com/ep1.srt',
    ): string {
        $pubDate = now()->subHour()->toRfc7231String();

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"
            xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
            xmlns:content="http://purl.org/rss/1.0/modules/content/"
            xmlns:podcast="https://podcastindex.org/namespace/1.0">
          <channel>
            <title>Test Podcast</title>
            <item>
              <title>{$title}</title>
              <link>{$guid}</link>
              <guid isPermaLink="false">{$guid}</guid>
              <description>{$desc}</description>
              <podcast:transcript url="{$transcriptUrl}" type="application/srt"/>
              <pubDate>{$pubDate}</pubDate>
            </item>
          </channel>
        </rss>
        XML;
    }

    private function srtTranscript(): string
    {
        return "1\n00:00:01,000 --> 00:00:04,000\nWelcome to the Marketing AI podcast.\n\n"
             . "2\n00:00:05,000 --> 00:00:09,000\nToday we discuss artificial intelligence in marketing.";
    }

    // =========================================================================
    // GROUP 1: Description mode
    // =========================================================================

    #[Test]
    public function description_mode_stores_content_encoded_as_summary_html(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(2, $stats['processed']);
        $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
        $this->assertStringContainsString('Full show notes for episode one', $summary->summary_html);
    }

    #[Test]
    public function description_mode_falls_back_to_description_when_content_encoded_absent(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);
        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep2')->first();
        $this->assertStringContainsString('Short teaser for episode two', $summary->summary_html);
    }

    // =========================================================================
    // GROUP 2: Summary mode
    // =========================================================================

    #[Test]
    public function summary_mode_produces_same_output_as_description_mode_in_v1(): void
    {
        $this->setPodcastProcessingMode('summary');
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);
        $this->llmMock->shouldNotReceive('generateContent');

        $stats = $this->processor->process($this->listSource);
        $this->assertSame(2, $stats['processed']);
    }

    // =========================================================================
    // GROUP 3: Search mode
    // =========================================================================

    #[Test]
    public function search_mode_matches_term_in_title(): void
    {
        $this->setPodcastProcessingMode('search', 'artificial intelligence');
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems(title1: 'How Artificial Intelligence Changes Marketing'))]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
        $this->assertTrue((bool) $summary->is_relevant);
    }

    #[Test]
    public function search_mode_fetches_transcript_when_metadata_does_not_match(): void
    {
        $this->setPodcastProcessingMode('search', 'artificial intelligence');

        Http::fake([
            self::FEED_URL      => Http::response($this->podcastFeedWithTranscript(
                title: 'General Episode', desc: 'General discussion.', transcriptUrl: self::TRANSCRIPT_URL,
            )),
            self::TRANSCRIPT_URL => Http::response($this->srtTranscript()),
        ]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertTrue((bool) $summary->is_relevant);
    }

    #[Test]
    public function search_mode_stores_not_relevant_when_no_metadata_or_transcript_match(): void
    {
        $this->setPodcastProcessingMode('search', 'astrophysics');
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems(title1: 'Cooking show', desc1: 'Recipes today.'))]);
        $this->llmMock->shouldNotReceive('generateContent');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
        $this->assertFalse((bool) $summary->is_relevant);
    }

    // =========================================================================
    // GROUP 4: First-run lookback window
    // =========================================================================

    #[Test]
    public function first_run_skips_episodes_older_than_lookback_window(): void
    {
        Http::fake([
            self::FEED_URL => Http::response($this->podcastFeedTwoItems(
                pubDate1: now()->subDays(10)->toRfc7231String(),
                pubDate2: now()->subHour()->toRfc7231String(),
            )),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(1, $stats['processed']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep1']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep2']);
    }

    #[Test]
    public function first_run_inserts_bookmark_pointing_to_newest_processed_episode(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertNotNull($bookmark);
        $this->assertSame('https://example.com/ep1', $bookmark->source_url);
    }

    #[Test]
    public function first_run_with_no_items_within_lookback_window_inserts_no_bookmark(): void
    {
        config(['processing.first_run_lookback_days' => 2]);

        Http::fake([
            self::FEED_URL => Http::response($this->podcastFeedTwoItems(
                pubDate1: now()->subDays(10)->toRfc7231String(),
                pubDate2: now()->subDays(11)->toRfc7231String(),
            )),
        ]);

        $this->processor->process($this->listSource);

        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));
    }

    // =========================================================================
    // GROUP 5: Bookmark — regular run stop conditions
    // =========================================================================

    #[Test]
    public function regular_run_stops_at_bookmarked_episode_url(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/ep2',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id'       => $this->listSource->id,
            'last_fetched_at'      => now()->subDay(),
            'consecutive_failures' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep1']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep2']);
    }

    #[Test]
    public function regular_run_stops_when_episode_is_older_than_bookmark_processed_at(): void
    {
        $bookmarkTime = now()->subHours(5);

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/deleted-ep',
            'processed_at'   => $bookmarkTime,
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id'       => $this->listSource->id,
            'last_fetched_at'      => now()->subDay(),
            'consecutive_failures' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        Http::fake([
            self::FEED_URL => Http::response($this->podcastFeedTwoItems(
                pubDate1: now()->subHour()->toRfc7231String(),
                pubDate2: $bookmarkTime->subHour()->toRfc7231String(),
            )),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep1']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep2']);
    }

    // =========================================================================
    // GROUP 6: Bookmark — rotation
    // =========================================================================

    #[Test]
    public function bookmark_is_rotated_to_newest_processed_episode_after_regular_run(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/ep2',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id'       => $this->listSource->id,
            'last_fetched_at'      => now()->subDay(),
            'consecutive_failures' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame('https://example.com/ep1', $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // GROUP 7: Bookmark — no rotation when nothing processed
    // =========================================================================

    #[Test]
    public function bookmark_unchanged_when_no_new_episodes_found(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/ep1',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id'       => $this->listSource->id,
            'last_fetched_at'      => now()->subDay(),
            'consecutive_failures' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['processed']);
        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame('https://example.com/ep1', $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // GROUP 8: Feed fetch failures and auto-suspension
    // =========================================================================

    #[Test]
    public function http_500_records_failure_and_increments_consecutive_failures(): void
    {
        Http::fake([self::FEED_URL => Http::response('Server Error', 500)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['errors']);
        $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame(1, $tracking->consecutive_failures);
    }

    #[Test]
    public function five_consecutive_failures_suspend_source_and_raise_admin_alert(): void
    {
        DB::table('list_source_tracking')->insert([
            'list_source_id'       => $this->listSource->id,
            'last_fetched_at'      => now()->subMinutes(5),
            'consecutive_failures' => 4,
            'error_message'        => 'Previous failure.',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        Http::fake([self::FEED_URL => Http::response('', 500)]);

        $this->processor->process($this->listSource);

        $listSource = DB::table('list_sources')->find($this->listSource->id);
        $this->assertTrue((bool) $listSource->suspended);
        $this->assertDatabaseHas('admin_alerts', ['category' => 'podcast']);
    }

    // =========================================================================
    // GROUP 9: Data integrity
    // =========================================================================

    #[Test]
    public function summaries_row_contains_correct_values(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems(
            guid1: 'https://example.com/ep1', title1: 'My Episode Title',
        ))]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
        $this->assertSame($this->user->id, $summary->user_id);
        $this->assertSame('My Episode Title', $summary->source_title);
        $this->assertSame('description', $summary->processing_mode);
        $this->assertTrue((bool) $summary->is_relevant);
        $this->assertFalse((bool) $summary->included_in_digest);
    }

    #[Test]
    public function tracking_row_is_updated_after_successful_run(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->podcastFeedTwoItems())]);

        $this->processor->process($this->listSource);

        $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
        $this->assertNotNull($tracking->last_fetched_at);
        $this->assertSame(0, $tracking->consecutive_failures);
    }

    // =========================================================================
    // GROUP 10: Edge cases
    // =========================================================================

    #[Test]
    public function missing_podcast_record_returns_empty_stats(): void
    {
        DB::table('list_sources')->where('id', $this->listSource->id)->update(['sourceable_id' => 99999]);
        $listSource = DB::table('list_sources')->find($this->listSource->id);

        $stats = $this->processor->process($listSource);

        $this->assertSame(0, $stats['fetched']);
        $this->assertSame(0, $stats['processed']);
    }

    #[Test]
    public function item_with_null_pubDate_is_processed_without_error(): void
    {
        $feedNoPubDate = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Test Podcast</title>
            <item>
              <title>No Date Episode</title>
              <link>https://example.com/nodateepisode</link>
              <description>No pubDate on this one.</description>
            </item>
          </channel>
        </rss>
        XML;

        Http::fake([self::FEED_URL => Http::response($feedNoPubDate)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }
}