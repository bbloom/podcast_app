<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/TextBasedRssContentProcessorTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\ArticleExtractorService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\TextBasedRss\Services\TextBasedRssContentProcessor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextBasedRssContentProcessorTest extends TestCase
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

        $this->user = User::factory()->create();
        $this->list = ListModel::factory()->forUser($this->user)->create();
        $this->feed = TextBasedRssFeed::factory()->forUser($this->user)->create([
            'rss_url' => 'https://example.com/feed.xml',
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
            ->where('sourceable_type', 'text_based_rss_feed')
            ->first();

        $this->llmMock       = $this->mock(LlmService::class);
        $this->extractorMock = $this->mock(ArticleExtractorService::class);

        $this->processor = new TextBasedRssContentProcessor(
            $this->llmMock,
            $this->extractorMock,
        );
    }

    // =========================================================================
    // Helpers & Fixtures
    // =========================================================================

    private function setRssProcessingMode(string $mode, ?string $searchTerms = null): void
    {
        DB::table('list_sources')
            ->where('id', $this->listSource->id)
            ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
        $this->listSource = DB::table('list_sources')->find($this->listSource->id);
    }

    private function rss2FeedWithItems(): string
    {
        $date1 = now()->subHours(2)->toRfc7231String();
        $date2 = now()->subHour()->toRfc7231String();

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Tech Daily</title>
            <item>
              <title>Article One</title>
              <link>https://example.com/article-1</link>
              <description>First article description.</description>
              <pubDate>{$date1}</pubDate>
            </item>
            <item>
              <title>Article Two</title>
              <link>https://example.com/article-2</link>
              <description>Second article description.</description>
              <pubDate>{$date2}</pubDate>
            </item>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedSingleItem(string $url, string $title, ?string $pubDate, string $description = 'Article description.'): string
    {
        $pubDateTag = $pubDate ? "<pubDate>{$pubDate}</pubDate>" : '';

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Single Item Feed</title>
            <item>
              <title>{$title}</title>
              <link>{$url}</link>
              <description>{$description}</description>
              {$pubDateTag}
            </item>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedWithNoDescriptions(): string
    {
        $date = now()->subHour()->toRfc7231String();

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <title>Tech Daily</title>
            <item>
              <title>Article One</title>
              <link>https://example.com/article-1</link>
              <pubDate>{$date}</pubDate>
            </item>
          </channel>
        </rss>
        XML;
    }

    private function rss2FeedEmpty(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel><title>Empty Feed</title></channel>
        </rss>
        XML;
    }

    // =========================================================================
    // GROUP 1: Description mode
    // =========================================================================

    #[Test]
    public function description_mode_inserts_summary_with_wrapped_description(): void
    {
        $this->llmMock->shouldNotReceive('generateContent');
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(2, $stats['processed']);
        $summary = DB::table('summaries')->where('source_url', 'https://example.com/article-1')->first();
        $this->assertStringContainsString('First article description.', $summary->summary_html);
        $this->assertStringStartsWith('<p>', $summary->summary_html);
    }

    #[Test]
    public function description_mode_stores_null_summary_html_when_description_is_empty(): void
    {
        $this->llmMock->shouldNotReceive('generateContent');
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithNoDescriptions())]);
        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertNull($summary->summary_html);
        $this->assertTrue((bool) $summary->is_relevant);
    }

    // =========================================================================
    // GROUP 2: Summary mode
    // =========================================================================

    #[Test]
    public function summary_mode_extracts_article_text_calls_llm_and_stores_result(): void
    {
        $this->setRssProcessingMode('summary');
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);
        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Full article text.');
        $this->llmMock->shouldReceive('generateContent')->andReturn('<p>LLM summary.</p>');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame('<p>LLM summary.</p>', $summary->summary_html);
    }

    #[Test]
    public function summary_mode_stores_unavailable_fallback_when_all_text_sources_fail(): void
    {
        $this->setRssProcessingMode('summary');
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithNoDescriptions())]);
        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn(null);
        $this->llmMock->shouldNotReceive('generateContent');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertStringContainsString('unavailable', $summary->summary_html);
    }

    // =========================================================================
    // GROUP 3: Search mode
    // =========================================================================

    #[Test]
    public function search_mode_tier1_matches_title_and_summarises(): void
    {
        $this->setRssProcessingMode('search', 'artificial intelligence');

        $date = now()->subHour()->toRfc7231String();
        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New developments in artificial intelligence</title>
            <link>https://example.com/ai-article</link>
            <description>Generic description.</description>
            <pubDate>{$date}</pubDate>
          </item>
        </channel></rss>
        XML)]);

        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Article about AI.');
        $this->llmMock->shouldReceive('generateContent')->once()->andReturn('<p>AI Summary.</p>');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertTrue((bool) $summary->is_relevant);
    }

    #[Test]
    public function search_mode_tier3_not_relevant_stores_row_with_is_relevant_false(): void
    {
        $this->setRssProcessingMode('search', 'astrophysics');

        $date = now()->subHour()->toRfc7231String();
        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Cooking Blog</title>
          <item><title>How to make pasta</title>
            <link>https://example.com/pasta</link>
            <description>A simple pasta recipe.</description>
            <pubDate>{$date}</pubDate>
          </item>
        </channel></rss>
        XML)]);

        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('This is entirely about cooking pasta.');
        $this->llmMock->shouldReceive('generateContent')->once()->andReturn('NOT_RELEVANT');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertFalse((bool) $summary->is_relevant);
        $this->assertNull($summary->summary_html);
    }

    // =========================================================================
    // GROUP 4: First-run lookback window
    // =========================================================================

    #[Test]
    public function first_run_skips_entries_older_than_lookback_window(): void
    {
        config(['processing.first_run_lookback_days' => 2]);

        $recentDate = now()->subHour()->toRfc7231String();

        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>Recent</title><link>https://example.com/recent</link>
            <description>Desc.</description><pubDate>{$recentDate}</pubDate></item>
          <item><title>Old</title><link>https://example.com/old</link>
            <description>Desc.</description><pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate></item>
        </channel></rss>
        XML)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/recent']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/old']);
    }

    #[Test]
    public function first_run_inserts_bookmark_pointing_to_newest_processed_entry(): void
    {
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);

        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertNotNull($bookmark);
        $this->assertSame('https://example.com/article-1', $bookmark->source_url);
    }

    #[Test]
    public function first_run_with_no_items_within_lookback_window_inserts_no_bookmark(): void
    {
        config(['processing.first_run_lookback_days' => 2]);

        Http::fake(['example.com/feed.xml' => Http::response(
            $this->rss2FeedSingleItem('https://example.com/old', 'Old', 'Mon, 01 Jan 2024 10:00:00 +0000')
        )]);

        $this->processor->process($this->listSource);

        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));
    }

    // =========================================================================
    // GROUP 5: Bookmark — regular run stop conditions
    // =========================================================================

    #[Test]
    public function regular_run_stops_at_bookmarked_entry_url(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/article-1',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subDay(),
            'consecutive_failures' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $newerDate = now()->subMinutes(30)->toRfc7231String();
        $olderDate = now()->subHours(2)->toRfc7231String();

        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New Item</title><link>https://example.com/new-item</link>
            <description>New.</description><pubDate>{$newerDate}</pubDate></item>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$olderDate}</pubDate></item>
        </channel></rss>
        XML)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/new-item']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/article-1']);
    }

    #[Test]
    public function regular_run_stops_when_entry_is_older_than_bookmark_processed_at(): void
    {
        $bookmarkTime = now()->subHours(5);

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/deleted-article',
            'processed_at'   => $bookmarkTime,
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subDay(),
            'consecutive_failures' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $newDate = now()->subHour()->toRfc7231String();
        $oldDate = $bookmarkTime->subHour()->toRfc7231String();

        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New</title><link>https://example.com/new</link>
            <description>New.</description><pubDate>{$newDate}</pubDate></item>
          <item><title>Old</title><link>https://example.com/very-old</link>
            <description>Old.</description><pubDate>{$oldDate}</pubDate></item>
        </channel></rss>
        XML)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['processed']);
        $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/new']);
        $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/very-old']);
    }

    // =========================================================================
    // GROUP 6: Bookmark — rotation
    // =========================================================================

    #[Test]
    public function bookmark_is_rotated_to_newest_processed_entry_after_regular_run(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/article-1',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subDay(),
            'consecutive_failures' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $newerDate = now()->subMinutes(30)->toRfc7231String();
        $olderDate = now()->subHours(2)->toRfc7231String();

        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New</title><link>https://example.com/new-item</link>
            <description>New.</description><pubDate>{$newerDate}</pubDate></item>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$olderDate}</pubDate></item>
        </channel></rss>
        XML)]);

        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame('https://example.com/new-item', $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // GROUP 7: Bookmark — no rotation when nothing processed
    // =========================================================================

    #[Test]
    public function bookmark_unchanged_when_no_new_entries_found(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/article-1',
            'processed_at'   => now()->subDay(),
        ]);

        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subDay(),
            'consecutive_failures' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $oldDate = now()->subHours(3)->toRfc7231String();

        Http::fake(['example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$oldDate}</pubDate></item>
        </channel></rss>
        XML)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['processed']);
        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame('https://example.com/article-1', $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // GROUP 8: Feed fetch failures and auto-suspension
    // =========================================================================

    #[Test]
    public function feed_fetch_http_500_records_failure_and_increments_counter(): void
    {
        Http::fake(['example.com/feed.xml' => Http::response('Server Error', 500)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['errors']);
        $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame(1, $tracking->consecutive_failures);
    }

    #[Test]
    public function five_consecutive_failures_suspend_source_and_raise_admin_alert(): void
    {
        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subMinutes(5),
            'consecutive_failures' => 4, 'error_message' => 'Previous failure.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Http::fake(['example.com/feed.xml' => Http::response('', 500)]);

        $this->processor->process($this->listSource);

        $listSource = DB::table('list_sources')->find($this->listSource->id);
        $this->assertTrue((bool) $listSource->suspended);
        $this->assertDatabaseHas('admin_alerts', ['category' => 'text_based_rss']);
    }

    #[Test]
    public function successful_run_resets_consecutive_failures(): void
    {
        DB::table('list_source_tracking')->insert([
            'list_source_id' => $this->listSource->id, 'last_fetched_at' => now()->subMinutes(5),
            'consecutive_failures' => 3, 'error_message' => 'Prior failure.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);

        $this->processor->process($this->listSource);

        $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame(0, $tracking->consecutive_failures);
        $this->assertNull($tracking->error_message);
    }

    // =========================================================================
    // GROUP 9: LLM failure modes
    // =========================================================================

    #[Test]
    public function summary_mode_llm_auth_exception_stores_error_html_and_raises_tier3_alert(): void
    {
        $this->setRssProcessingMode('summary');
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);
        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Article text.');
        $this->llmMock->shouldReceive('generateContent')
            ->andThrow(new LlmAuthenticationException('API key invalid.', providerSlug: 'google', modelSlug: 'gemini-2.5-flash'));

        $stats = $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertStringContainsString('authentication error', strtolower($summary->summary_html));
        $this->assertDatabaseHas('admin_alerts', ['tier' => 3]);
        $this->assertSame(2, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }

    #[Test]
    public function summary_mode_llm_rate_limit_stores_deferred_html(): void
    {
        $this->setRssProcessingMode('summary');
        Http::fake(['example.com/feed.xml' => Http::response(
            $this->rss2FeedSingleItem('https://example.com/article-1', 'Article One', now()->toRfc7231String())
        )]);
        $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Text.');
        $this->llmMock->shouldReceive('generateContent')->andThrow(new LlmRateLimitException('Rate limited.'));

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertStringContainsString('rate limited', strtolower($summary->summary_html));
    }

    // =========================================================================
    // GROUP 10: Data integrity
    // =========================================================================

    #[Test]
    public function summary_row_has_correct_user_id_from_list_owner(): void
    {
        Http::fake(['example.com/feed.xml' => Http::response(
            $this->rss2FeedSingleItem('https://example.com/article-1', 'Article One', now()->toRfc7231String())
        )]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame($this->user->id, $summary->user_id);
    }

    #[Test]
    public function summary_row_has_included_in_digest_false_by_default(): void
    {
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedWithItems())]);

        $this->processor->process($this->listSource);

        DB::table('summaries')->where('list_source_id', $this->listSource->id)->get()
            ->each(fn ($s) => $this->assertFalse((bool) $s->included_in_digest));
    }

    // =========================================================================
    // GROUP 11: Edge cases
    // =========================================================================

    #[Test]
    public function missing_feed_record_returns_zero_stats_gracefully(): void
    {
        $this->feed->delete();
        Http::fake();

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['fetched']);
        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }

    #[Test]
    public function empty_feed_with_no_items_processes_cleanly(): void
    {
        Http::fake(['example.com/feed.xml' => Http::response($this->rss2FeedEmpty())]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['fetched']);
        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }
}