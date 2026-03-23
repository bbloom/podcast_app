<?php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\ContentSources\Podcasts\Models\Podcast;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Podcasts\Services\PodcastContentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * PodcastContentProcessorTest
 *
 * TEST GROUPS
 * ───────────
 *   1.  Description mode — happy path
 *   2.  Summary mode
 *   3.  Search mode — metadata and transcript
 *   4.  First-run lookback window
 *   5.  Bookmark — regular run stop conditions
 *   6.  Bookmark — rotation after processing
 *   7.  Bookmark — no rotation when nothing processed
 *   8.  Feed fetch failures and auto-suspension
 *   9.  Data integrity
 *  10.  Edge cases
 */

uses(RefreshDatabase::class);

const PODCAST_FEED_URL = 'https://feeds.example.com/podcast.xml';
const TRANSCRIPT_URL   = 'https://transcripts.example.com/ep1.srt';

beforeEach(function () {
    $this->user    = User::factory()->create();
    $this->list    = ListModel::factory()->forUser($this->user)->create();
    $this->podcast = Podcast::factory()->forUser($this->user)->create([
        'rss_url' => PODCAST_FEED_URL,
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
});

// =============================================================================
// Helpers & Fixtures
// =============================================================================

function setPodcastProcessingMode(string $mode, ?string $searchTerms = null): void
{
    DB::table('list_sources')
        ->where('id', test()->listSource->id)
        ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
    test()->listSource = DB::table('list_sources')->find(test()->listSource->id);
}

function podcastFeedTwoItems(
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

function podcastFeedWithTranscript(
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

function srtTranscript(): string
{
    return "1\n00:00:01,000 --> 00:00:04,000\nWelcome to the Marketing AI podcast.\n\n"
         . "2\n00:00:05,000 --> 00:00:09,000\nToday we discuss artificial intelligence in marketing.";
}

// =============================================================================
// GROUP 1: Description mode
// =============================================================================

test('description mode stores content:encoded as summary_html', function () {
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(2);
    $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
    expect($summary->summary_html)->toContain('Full show notes for episode one');
});

test('description mode falls back to description when content:encoded absent', function () {
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);
    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep2')->first();
    expect($summary->summary_html)->toContain('Short teaser for episode two');
});

// =============================================================================
// GROUP 2: Summary mode
// =============================================================================

test('summary mode produces same output as description mode in v1', function () {
    setPodcastProcessingMode('summary');
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);
    $this->llmMock->shouldNotReceive('generateContent');

    $stats = $this->processor->process($this->listSource);
    expect($stats['processed'])->toBe(2);
});

// =============================================================================
// GROUP 3: Search mode
// =============================================================================

test('search mode matches term in title', function () {
    setPodcastProcessingMode('search', 'artificial intelligence');
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(title1: 'How Artificial Intelligence Changes Marketing'))]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
    expect($summary->is_relevant)->toBeTrue();
});

test('search mode fetches transcript when metadata does not match', function () {
    setPodcastProcessingMode('search', 'artificial intelligence');

    Http::fake([
        PODCAST_FEED_URL => Http::response(podcastFeedWithTranscript(
            title: 'General Episode', desc: 'General discussion.', transcriptUrl: TRANSCRIPT_URL,
        )),
        TRANSCRIPT_URL => Http::response(srtTranscript()),
    ]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->is_relevant)->toBeTrue();
});

test('search mode stores not relevant when no metadata or transcript match', function () {
    setPodcastProcessingMode('search', 'astrophysics');
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(title1: 'Cooking show', desc1: 'Recipes today.'))]);
    $this->llmMock->shouldNotReceive('generateContent');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
    expect($summary->is_relevant)->toBeFalse();
});

// =============================================================================
// GROUP 4: First-run lookback window
// =============================================================================

test('first run skips episodes older than lookback window', function () {
    Http::fake([
        PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(
            pubDate1: now()->subDays(10)->toRfc7231String(),
            pubDate2: now()->subHour()->toRfc7231String(),
        )),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['skipped'])->toBe(1);
    expect($stats['processed'])->toBe(1);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep1']);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep2']);
});

test('first run inserts bookmark pointing to newest processed episode', function () {
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark)->not->toBeNull();
    expect($bookmark->source_url)->toBe('https://example.com/ep1');
});

test('first run with no items within lookback window inserts no bookmark', function () {
    config(['processing.first_run_lookback_days' => 2]);

    Http::fake([
        PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(
            pubDate1: now()->subDays(10)->toRfc7231String(),
            pubDate2: now()->subDays(11)->toRfc7231String(),
        )),
    ]);

    $this->processor->process($this->listSource);

    expect(ContentAlreadyProcessed::findBookmark($this->listSource->id))->toBeNull();
});

// =============================================================================
// GROUP 5: Bookmark — regular run stop conditions
// =============================================================================

test('regular run stops at bookmarked episode url', function () {
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

    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep1']);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep2']);
});

test('regular run stops when episode is older than bookmark processed_at', function () {
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
        PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(
            pubDate1: now()->subHour()->toRfc7231String(),
            pubDate2: $bookmarkTime->subHour()->toRfc7231String(),
        )),
    ]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    $this->assertDatabaseHas('summaries', ['source_url' => 'https://example.com/ep1']);
    $this->assertDatabaseMissing('summaries', ['source_url' => 'https://example.com/ep2']);
});

// =============================================================================
// GROUP 6: Bookmark — rotation
// =============================================================================

test('bookmark is rotated to newest processed episode after regular run', function () {
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

    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $this->processor->process($this->listSource);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark->source_url)->toBe('https://example.com/ep1');
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// GROUP 7: Bookmark — no rotation when nothing processed
// =============================================================================

test('bookmark unchanged when no new episodes found', function () {
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

    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(0);
    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
    expect($bookmark->source_url)->toBe('https://example.com/ep1');
    expect(ContentAlreadyProcessed::count())->toBe(1);
});

// =============================================================================
// GROUP 8: Feed fetch failures and auto-suspension
// =============================================================================

test('http 500 records failure and increments consecutive_failures', function () {
    Http::fake([PODCAST_FEED_URL => Http::response('Server Error', 500)]);

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

    Http::fake([PODCAST_FEED_URL => Http::response('', 500)]);

    $this->processor->process($this->listSource);

    $listSource = DB::table('list_sources')->find($this->listSource->id);
    expect($listSource->suspended)->toBeTrue();
    $this->assertDatabaseHas('admin_alerts', ['category' => 'podcast']);
});

// =============================================================================
// GROUP 9: Data integrity
// =============================================================================

test('summaries row contains correct values', function () {
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems(
        guid1: 'https://example.com/ep1', title1: 'My Episode Title',
    ))]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('source_url', 'https://example.com/ep1')->first();
    expect($summary->user_id)->toBe($this->user->id);
    expect($summary->source_title)->toBe('My Episode Title');
    expect($summary->processing_mode)->toBe('description');
    expect($summary->is_relevant)->toBeTrue();
    expect($summary->included_in_digest)->toBeFalse();
});

test('tracking row is updated after successful run', function () {
    Http::fake([PODCAST_FEED_URL => Http::response(podcastFeedTwoItems())]);

    $this->processor->process($this->listSource);

    $tracking = DB::table('list_source_tracking')->where('list_source_id', $this->listSource->id)->first();
    expect($tracking->last_fetched_at)->not->toBeNull();
    expect($tracking->consecutive_failures)->toBe(0);
});

// =============================================================================
// GROUP 10: Edge cases
// =============================================================================

test('missing podcast record returns empty stats', function () {
    DB::table('list_sources')->where('id', $this->listSource->id)->update(['sourceable_id' => 99999]);
    $listSource = DB::table('list_sources')->find($this->listSource->id);

    $stats = $this->processor->process($listSource);

    expect($stats['fetched'])->toBe(0);
    expect($stats['processed'])->toBe(0);
});

test('item with null pubDate is processed without error', function () {
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

    Http::fake([PODCAST_FEED_URL => Http::response($feedNoPubDate)]);

    $stats = $this->processor->process($this->listSource);

    expect($stats['processed'])->toBe(1);
    expect($stats['errors'])->toBe(0);
});