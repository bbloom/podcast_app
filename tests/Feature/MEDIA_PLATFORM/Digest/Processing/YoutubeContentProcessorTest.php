<?php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Youtube\Services\YoutubeContentProcessor;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

/**
 * YoutubeContentProcessorTest
 *
 * APPROACH
 * ────────
 * Real DB, fake HTTP, mocked LlmService, faked Process facade.
 *
 * TEST GROUPS
 * ───────────
 *   0.  cleanDescription() — unit tests
 *   1.  Description mode
 *   2.  Summary mode
 *   3.  Search mode
 *   4.  First-run lookback window
 *   5.  Bookmark — regular run stop conditions
 *   6.  Bookmark — rotation after processing
 *   7.  Bookmark — no rotation when nothing processed
 *   8.  Deduplication removed (summaries table no longer used for dedup)
 *   9.  YouTube API failures and auto-suspension
 *  10.  Transcript script failures
 *  11.  LLM failure modes
 *  12.  Data integrity
 *  13.  Edge cases
 */

uses(RefreshDatabase::class);

const YT_CHANNEL_ID  = 'UCtest1234567890123456';
const YT_PLAYLIST_ID = 'UUtest1234567890123456';
const YT_API_BASE    = 'www.googleapis.com/youtube/v3/playlistItems*';

beforeEach(function () {
    $this->user    = User::factory()->create();
    $this->list    = ListModel::factory()->forUser($this->user)->create();
    $this->channel = YoutubeChannel::factory()->forUser($this->user)->create([
        'channel_id' => YT_CHANNEL_ID,
    ]);

    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => $this->channel->id,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->listSource = DB::table('list_sources')
        ->where('sourceable_id', $this->channel->id)
        ->where('sourceable_type', 'youtube_channel')
        ->first();

    $this->llmMock = mock(LlmService::class);
    app()->instance(LlmService::class, $this->llmMock);
    $this->processor = new YoutubeContentProcessor($this->llmMock);
});

// =============================================================================
// Helpers
// =============================================================================

function setYtProcessingMode(string $mode, ?string $searchTerms = null): void
{
    DB::table('list_sources')
        ->where('id', test()->listSource->id)
        ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
    test()->listSource = DB::table('list_sources')->find(test()->listSource->id);
}

function ytPlaylistApiResponse(array $items): array
{
    return [
        'kind'     => 'youtube#playlistItemListResponse',
        'etag'     => 'fake-etag',
        'pageInfo' => ['totalResults' => count($items), 'resultsPerPage' => 50],
        'items'    => $items,
    ];
}

function ytVideoSnippet(string $videoId, string $title, string $description, string $publishedAt): array
{
    return [
        'kind'   => 'youtube#playlistItem',
        'etag'   => 'fake-etag-' . $videoId,
        'snippet' => [
            'publishedAt' => $publishedAt,
            'title'       => $title,
            'description' => $description,
            'resourceId'  => ['kind' => 'youtube#video', 'videoId' => $videoId],
        ],
        'contentDetails' => [
            'videoId'          => $videoId,
            'videoPublishedAt' => $publishedAt,
        ],
    ];
}

// =============================================================================
// GROUP 0: cleanDescription() — unit tests
// =============================================================================

test('cleanDescription returns body text when no boilerplate present', function () {
    $desc = "This is a great video about Laravel.\nWe cover queues and jobs.";
    expect($this->processor->cleanDescription($desc))->toBe("This is a great video about Laravel.\nWe cover queues and jobs.");
});

test('cleanDescription stops at first bare URL', function () {
    $desc = "Great video about testing.\nhttps://sponsor.com\nMore text after.";
    expect($this->processor->cleanDescription($desc))->toBe('Great video about testing.');
});

test('cleanDescription stops at chapter timestamp', function () {
    $desc = "A tutorial on PHP.\n0:00 Intro\n1:23 Topic one";
    expect($this->processor->cleanDescription($desc))->toBe('A tutorial on PHP.');
});

test('cleanDescription stops at timestamp with hours', function () {
    $desc = "A long video.\n1:23:45 Deep dive";
    expect($this->processor->cleanDescription($desc))->toBe('A long video.');
});

test('cleanDescription stops at known section headers', function () {
    foreach (['CHAPTERS', 'TIMESTAMPS', 'LINKS', 'SPONSORS', 'FOLLOW', 'CONNECT',
              'SUPPORT', 'AFFILIATE', 'MERCH', 'SOCIALS', 'PATREON'] as $header) {
        $desc = "Good content here.\n{$header}\nhttps://example.com";
        expect($this->processor->cleanDescription($desc))
            ->toBe('Good content here.')
            ->not->toContain($header);
    }
});

test('cleanDescription stops at section headers with trailing colon', function () {
    $desc = "Good content here.\nLINKS:\nhttps://example.com";
    expect($this->processor->cleanDescription($desc))->toBe('Good content here.');
});

test('cleanDescription skips subscribe line before content and returns body', function () {
    $desc = "Subscribe for more videos!\nThis episode covers deployment strategies.";
    expect($this->processor->cleanDescription($desc))->toBe('This episode covers deployment strategies.');
});

test('cleanDescription stops at subscribe line once content has started', function () {
    $desc = "This episode covers deployment strategies.\nSubscribe for more!";
    expect($this->processor->cleanDescription($desc))->toBe('This episode covers deployment strategies.');
});

test('cleanDescription looks past blank lines and does not stop on them', function () {
    $desc = "First paragraph.\n\nSecond paragraph.\nhttps://link.com";
    expect($this->processor->cleanDescription($desc))->toBe("First paragraph.\n\nSecond paragraph.");
});

test('cleanDescription returns no description provided when nothing useful found', function () {
    $desc = "https://subscribe.com\n0:00 Intro\nLINKS:";
    expect($this->processor->cleanDescription($desc))->toBe('No description provided');
});

test('cleanDescription returns no description provided for empty string', function () {
    expect($this->processor->cleanDescription(''))->toBe('No description provided');
});

test('cleanDescription trims trailing blank lines from collected body', function () {
    $desc = "Real content.\n\n\nhttps://link.com";
    expect($this->processor->cleanDescription($desc))->toBe('Real content.');
});

// =============================================================================
// GROUP 1: Description mode
// =============================================================================

test('description mode stores cleaned description', function () {
    $this->llmMock->shouldNotReceive('generateContent');
    Process::fake();

    $raw = "A great video about Laravel queues.\nhttps://sponsor.com\n0:00 Intro";

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
        ])),
    ]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('source_url', 'https://www.youtube.com/watch?v=vid1')->first();
    expect($summary->summary_html)->toContain('A great video about Laravel queues.');
    expect($summary->summary_html)->not->toContain('sponsor.com');
    expect($summary->summary_html)->not->toContain('0:00');
    expect($summary->summary_html)->toStartWith('<p>');
});

test('description mode stores no description provided when description is empty', function () {
    $this->llmMock->shouldNotReceive('generateContent');
    Process::fake();

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', '', now()->subHour()->toIso8601String()),
        ])),
    ]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toContain('No description provided');
    expect($summary->is_relevant)->toBeTrue();
});

test('description mode stores raw description in source_description column unchanged', function () {
    $this->llmMock->shouldNotReceive('generateContent');
    Process::fake();

    $raw = "Good content.\nhttps://sponsor.com";

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
        ])),
    ]);

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    // source_description preserves the raw value; summary_html is cleaned.
    expect($summary->source_description)->toBe($raw);
    expect($summary->summary_html)->not->toContain('sponsor.com');
});

// =============================================================================
// GROUP 2: Summary mode
// =============================================================================

test('summary mode fetches transcript calls llm and stores result', function () {
    setYtProcessingMode('summary');

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', 'Desc.', now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake([
        '*get_transcript.py vid1*' => Process::result(
            output: json_encode(['transcript' => 'Full transcript text.']),
        ),
    ]);

    $this->llmMock->shouldReceive('generateContent')->once()->andReturn('<p>LLM summary.</p>');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toBe('<p>LLM summary.</p>');
});

test('summary mode falls back to cleaned description when transcript unavailable', function () {
    setYtProcessingMode('summary');

    $raw = "Really useful description content.\nhttps://sponsor.com\n0:00 Intro";

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake([
        '*get_transcript.py vid1*' => Process::result(
            output: json_encode(['transcript' => 'ERROR: No captions available.']),
        ),
    ]);

    $this->llmMock->shouldNotReceive('generateContent');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toContain('Really useful description content.');
    expect($summary->summary_html)->not->toContain('sponsor.com');
    expect($summary->summary_html)->not->toContain('0:00');
});

test('summary mode stores unavailable message when transcript and description both absent', function () {
    setYtProcessingMode('summary');

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Video One', '', now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake([
        '*get_transcript.py vid1*' => Process::result(
            output: json_encode(['transcript' => 'ERROR: No captions available.']),
        ),
    ]);

    $this->llmMock->shouldNotReceive('generateContent');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect(strtolower($summary->summary_html))->toContain('unavailable');
});

// =============================================================================
// GROUP 3: Search mode
// =============================================================================

test('search mode tier1 matches title and summarises', function () {
    setYtProcessingMode('search', 'quantum computing');

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Intro to quantum computing', 'Desc.', now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake(['*get_transcript.py vid1*' => Process::result(output: json_encode(['transcript' => 'Transcript.']))]);
    $this->llmMock->shouldReceive('generateContent')->once()->andReturn('<p>Summary.</p>');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->is_relevant)->toBeTrue();
});

test('search mode tier3 not relevant stores row with is_relevant false', function () {
    setYtProcessingMode('search', 'astrophysics');

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Cooking Pasta', 'How to make pasta.', now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake(['*get_transcript.py vid1*' => Process::result(output: json_encode(['transcript' => 'Pasta cooking transcript.']))]);
    $this->llmMock->shouldReceive('generateContent')->once()->andReturn('NOT_RELEVANT');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->is_relevant)->toBeFalse();
});

test('search mode tier3 falls back to cleaned description when transcript unavailable', function () {
    setYtProcessingMode('search', 'astrophysics');

    $raw = "A deep dive into black holes and dark matter.\nhttps://sponsor.com";

    Http::fake([
        YT_API_BASE => Http::response(ytPlaylistApiResponse([
            ytVideoSnippet('vid1', 'Cooking Pasta', $raw, now()->subHour()->toIso8601String()),
        ])),
    ]);

    Process::fake([
        '*get_transcript.py vid1*' => Process::result(
            output: json_encode(['transcript' => 'ERROR: No captions available.']),
        ),
    ]);

    // LLM should NOT be called — we never reach the semantic check.
    $this->llmMock->shouldNotReceive('generateContent');

    $this->processor->process($this->listSource);

    $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
    expect($summary->summary_html)->toContain('A deep dive into black holes');
    expect($summary->summary_html)->not->toContain('sponsor.com');
    expect($summary->is_relevant)->toBeTrue();
});