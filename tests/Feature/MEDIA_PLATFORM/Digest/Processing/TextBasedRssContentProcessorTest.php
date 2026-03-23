<?php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\ArticleExtractorService;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\TextBasedRss\Services\TextBasedRssContentProcessor;
use MediaPlatform\Digest\ContentSources\TextBasedRssFeeds\Models\TextBasedRssFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * TextBasedRssContentProcessorTest
 *
 * TEST GROUPS
 * ───────────
 *   1.  Description mode
 *   2.  Summary mode
 *   3.  Search mode
 *   4.  First-run lookback window
 *   5.  Bookmark — regular run stop conditions
 *   6.  Bookmark — rotation after processing
 *   7.  Bookmark — no rotation when nothing processed
 *   8.  Feed fetch failures and auto-suspension
 *   9.  LLM failure modes
 *  10.  Data integrity
 *  11.  Edge cases
 */

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

// =============================================================================
// Helpers & Fixtures
// =============================================================================

function setRssProcessingMode(string $mode, ?string $searchTerms = null): void
{
    DB::table('list_sources')
        ->where('id', test()->listSource->id)
        ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
    test()->listSource = DB::table('list_sources')->find(test()->listSource->id);
}

function rss2FeedWithItems(): string
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

function rss2FeedSingleItem(string $url, string $title, ?string $pubDate, string $description = 'Article description.'): string
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

function rss2FeedWithNoDescriptions(): string
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

function rss2FeedEmpty(): string
{
    return <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0">
      <channel><title>Empty Feed</title></channel>
    </rss>
    XML;
}

// =============================================================================
// GROUP 1: Description mode
// =============================================================================

test('description mode inserts summary with wrapped description', function () {
    $this->llmMock->shouldNotReceive('generateContent');

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(2);
    $summary = DB::table('summaries')->where('source_url', 'https://example.com/article-1')->first();
    expect($summary->summary_html)->toContain('First article description.');
    expect($summary->summary_html)->toStartWith('<p>');
});

test('description mode stores null summary_html when description is empty', function () {
    $this->llmMock->shouldNotReceive('generateContent');

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithNoDescriptions())]);
    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toBeNull();
    expect($summary->is_relevant)->toBeTrue();
});

// =============================================================================
// GROUP 2: Summary mode
// =============================================================================

test('summary mode extracts article text calls llm and stores result', function () {
    setRssProcessingMode('summary');

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);
    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Full article text.');
    $this->llmMock->shouldReceive('generateContent')->andReturn('<p>LLM summary.</p>');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toBe('<p>LLM summary.</p>');
});

test('summary mode stores unavailable fallback when all text sources fail', function () {
    setRssProcessingMode('summary');

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithNoDescriptions())]);
    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn(null);
    $this->llmMock->shouldNotReceive('generateContent');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toContain('unavailable');
});

// =============================================================================
// GROUP 3: Search mode
// =============================================================================

test('search mode tier1 matches title and summarises', function () {
    setRssProcessingMode('search', 'artificial intelligence');

    $date = now()->subHour()->toRfc7231String();
    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New developments in artificial intelligence</title>
            <link>https://example.com/ai-article</link>
            <description>Generic description.</description>
            <pubDate>{$date}</pubDate>
          </item>
        </channel></rss>
        XML),
    ]);

    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Article about AI.');
    $this->llmMock->shouldReceive('generateContent')->once()->andReturn('<p>AI Summary.</p>');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->is_relevant)->toBeTrue();
});

test('search mode tier3 not relevant stores row with is_relevant false', function () {
    setRssProcessingMode('search', 'astrophysics');

    $date = now()->subHour()->toRfc7231String();
    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Cooking Blog</title>
          <item><title>How to make pasta</title>
            <link>https://example.com/pasta</link>
            <description>A simple pasta recipe.</description>
            <pubDate>{$date}</pubDate>
          </item>
        </channel></rss>
        XML),
    ]);

    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('This is entirely about cooking pasta.');
    $this->llmMock->shouldReceive('generateContent')->once()->andReturn('NOT_RELEVANT');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->is_relevant)->toBeFalse();
    expect($summary->summary_html)->toBeNull();
});

// =============================================================================
// GROUP 4: First-run lookback window
// =============================================================================

test('first run skips entries older than lookback window', function () {
    config(['processing.first_run_lookback_days' => 2]);

    $recentDate = now()->subHour()->toRfc7231String();

    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>Recent</title><link>https://example.com/recent</link>
            <description>Desc.</description><pubDate>{$recentDate}</pubDate></item>
          <item><title>Old</title><link>https://example.com/old</link>
            <description>Desc.</description><pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate></item>
        </channel></rss>
        XML),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    expect($stats['skipped'])->toBe(1);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/recent']);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/old']);
});

test('first run inserts bookmark pointing to newest processed entry', function () {
    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark)->not->toBeNull();
    // Feed has two items; article-1 is listed first (oldest pubDate in fixture).
    // article-2 has a newer pubDate, but the feed lists article-1 first.
    // The bookmark should point to whichever is first in the feed.
    expect($bookmark->source_url)->toBe('https://example.com/article-1');
});

test('first run with no items within lookback window inserts no bookmark', function () {
    config(['processing.first_run_lookback_days' => 2]);

    Http::fake([
        'example.com/feed.xml' => Http::response(rss2FeedSingleItem(
            'https://example.com/old', 'Old', 'Mon, 01 Jan 2024 10:00:00 +0000'
        )),
    ]);

    $this->processor->process($this->listSource);

    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();
});

// =============================================================================
// GROUP 5: Bookmark — regular run stop conditions
// =============================================================================

test('regular run stops at bookmarked entry url', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/article-1',
        'processed_at'   => now()->subDay(),
    ]);

    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subDay(),
        'consecutive_failures' => 0,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $newerDate = now()->subMinutes(30)->toRfc7231String();
    $olderDate = now()->subHours(2)->toRfc7231String();

    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New Item</title><link>https://example.com/new-item</link>
            <description>New.</description><pubDate>{$newerDate}</pubDate></item>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$olderDate}</pubDate></item>
        </channel></rss>
        XML),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/new-item']);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/article-1']);
});

test('regular run stops when entry is older than bookmark processed_at', function () {
    $bookmarkTime = now()->subHours(5);

    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/deleted-article',
        'processed_at'   => $bookmarkTime,
    ]);

    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subDay(),
        'consecutive_failures' => 0,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $newDate = now()->subHour()->toRfc7231String();
    $oldDate = $bookmarkTime->subHour()->toRfc7231String();

    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New</title><link>https://example.com/new</link>
            <description>New.</description><pubDate>{$newDate}</pubDate></item>
          <item><title>Old</title><link>https://example.com/very-old</link>
            <description>Old.</description><pubDate>{$oldDate}</pubDate></item>
        </channel></rss>
        XML),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/new']);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/very-old']);
});

// =============================================================================
// GROUP 6: Bookmark — rotation
// =============================================================================

test('bookmark is rotated to newest processed entry after regular run', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/article-1',
        'processed_at'   => now()->subDay(),
    ]);

    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subDay(),
        'consecutive_failures' => 0,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $newerDate = now()->subMinutes(30)->toRfc7231String();
    $olderDate = now()->subHours(2)->toRfc7231String();

    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>New</title><link>https://example.com/new-item</link>
            <description>New.</description><pubDate>{$newerDate}</pubDate></item>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$olderDate}</pubDate></item>
        </channel></rss>
        XML),
    ]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark->source_url)->toBe('https://example.com/new-item');
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// GROUP 7: Bookmark — no rotation when nothing processed
// =============================================================================

test('bookmark unchanged when no new entries found', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSource->id,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/article-1',
        'processed_at'   => now()->subDay(),
    ]);

    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subDay(),
        'consecutive_failures' => 0,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $oldDate = now()->subHours(3)->toRfc7231String();

    Http::fake([
        'example.com/feed.xml' => Http::response(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel><title>Feed</title>
          <item><title>Article One</title><link>https://example.com/article-1</link>
            <description>Desc.</description><pubDate>{$oldDate}</pubDate></item>
        </channel></rss>
        XML),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark->source_url)->toBe('https://example.com/article-1');
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// GROUP 8: Feed fetch failures and auto-suspension
// =============================================================================

test('feed fetch http 500 records failure and increments counter', function () {
    Http::fake(['example.com/feed.xml' => Http::response('Server Error', 500)]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['errors'])->toBe(1);
    $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
    expect($tracking->consecutive_failures)->toBe(1);
});

test('five consecutive failures suspend source and raise admin alert', function () {
    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subMinutes(5),
        'consecutive_failures' => 4,
        'error_message'        => 'Previous failure.',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    Http::fake(['example.com/feed.xml' => Http::response('', 500)]);

    $this->processor->process($this->listSource);

    $listSource = DB::table('list_sources')->find($this->listSource->id);
    expect($listSource->suspended)->toBeTrue();
    $this->assertDatabaseHas('admin_alerts', ['category' => 'text_based_rss']);
});

test('successful run resets consecutive failures', function () {
    DB::table('list_source_tracking')->insert([
        'list_source_id'       => $this->listSource->id,
        'last_fetched_at'      => now()->subMinutes(5),
        'consecutive_failures' => 3,
        'error_message'        => 'Prior failure.',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);

    $this->processor->process($this->listSource);

    $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
    expect($tracking->consecutive_failures)->toBe(0);
    expect($tracking->error_message)->toBeNull();
});

// =============================================================================
// GROUP 9: LLM failure modes
// =============================================================================

test('summary mode llm auth exception stores error html and raises tier3 alert', function () {
    setRssProcessingMode('summary');

    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);
    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Article text.');
    $this->llmMock->shouldReceive('generateContent')
        ->andThrow(new LlmAuthenticationException('API key invalid.', providerSlug: 'google', modelSlug: 'gemini-2.5-flash'));

    $stats = $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect(strtolower($summary->summary_html))->toContain('authentication error');
    $this->assertDatabaseHas('admin_alerts', ['tier' => 3]);
    expect($stats['processed'])->toBe(2);
    expect($stats['errors'])->toBe(0);
});

test('summary mode llm rate limit stores deferred html', function () {
    setRssProcessingMode('summary');

    Http::fake(['example.com/feed.xml' => Http::response(
        rss2FeedSingleItem('https://example.com/article-1', 'Article One', now()->toRfc7231String())
    )]);
    $this->extractorMock->shouldReceive('fetchArticleText')->andReturn('Text.');
    $this->llmMock->shouldReceive('generateContent')->andThrow(new LlmRateLimitException('Rate limited.'));

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect(strtolower($summary->summary_html))->toContain('rate limited');
});

// =============================================================================
// GROUP 10: Data integrity
// =============================================================================

test('summary row has correct user_id from list owner', function () {
    Http::fake(['example.com/feed.xml' => Http::response(
        rss2FeedSingleItem('https://example.com/article-1', 'Article One', now()->toRfc7231String())
    )]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->user_id)->toBe($this->user->id);
});

test('summary row has included_in_digest false by default', function () {
    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedWithItems())]);

    $this->processor->process($this->listSource);

    DB::table('summaries')->where('list_source_id', $this->listSource->id)->get()
        ->each(fn ($s) => expect($s->included_in_digest)->toBeFalse());
});

// =============================================================================
// GROUP 11: Edge cases
// =============================================================================

test('missing feed record returns zero stats gracefully', function () {
    $this->feed->delete();
    Http::fake();

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(0);
    expect($stats['processed'])->toBe(0);
    expect($stats['errors'])->toBe(0);
});

test('empty feed with no items processes cleanly', function () {
    Http::fake(['example.com/feed.xml' => Http::response(rss2FeedEmpty())]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['fetched'])->toBe(0);
    expect($stats['processed'])->toBe(0);
    expect($stats['errors'])->toBe(0);
});