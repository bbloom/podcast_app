<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/YoutubeContentProcessorTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use MediaPlatform\Digest\Processing\Exceptions\LlmAuthenticationException;
use MediaPlatform\Digest\Processing\Exceptions\LlmRateLimitException;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Youtube\Services\YoutubeContentProcessor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YoutubeContentProcessorTest extends TestCase
{
    use RefreshDatabase;

    private const CHANNEL_ID  = 'UCtest1234567890123456';
    private const PLAYLIST_ID = 'UUtest1234567890123456';
    private const API_BASE    = 'www.googleapis.com/youtube/v3/playlistItems*';

    private User                    $user;
    private ListModel               $list;
    private YoutubeChannel          $channel;
    private object                  $listSource;
    private LlmService              $llmMock;
    private YoutubeContentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->list    = ListModel::factory()->forUser($this->user)->create();
        $this->channel = YoutubeChannel::factory()->forUser($this->user)->create([
            'channel_id' => self::CHANNEL_ID,
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
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function setYtProcessingMode(string $mode, ?string $searchTerms = null): void
    {
        DB::table('list_sources')
            ->where('id', $this->listSource->id)
            ->update(['processing_mode' => $mode, 'search_terms' => $searchTerms]);
        $this->listSource = DB::table('list_sources')->find($this->listSource->id);
    }

    private function ytPlaylistApiResponse(array $items): array
    {
        return [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake-etag',
            'pageInfo' => ['totalResults' => count($items), 'resultsPerPage' => 50],
            'items'    => $items,
        ];
    }

    private function ytVideoSnippet(string $videoId, string $title, string $description, string $publishedAt): array
    {
        return [
            'kind'           => 'youtube#playlistItem',
            'etag'           => 'fake-etag-' . $videoId,
            'snippet'        => [
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

    // =========================================================================
    // GROUP 0: cleanDescription() — unit tests
    // =========================================================================

    #[Test]
    public function cleanDescription_returns_body_text_when_no_boilerplate_present(): void
    {
        $desc = "This is a great video about Laravel.\nWe cover queues and jobs.";
        $this->assertSame($desc, $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_stops_at_first_bare_URL(): void
    {
        $desc = "Great video about testing.\nhttps://sponsor.com\nMore text after.";
        $this->assertSame('Great video about testing.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_stops_at_chapter_timestamp(): void
    {
        $desc = "A tutorial on PHP.\n0:00 Intro\n1:23 Topic one";
        $this->assertSame('A tutorial on PHP.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_stops_at_timestamp_with_hours(): void
    {
        $desc = "A long video.\n1:23:45 Deep dive";
        $this->assertSame('A long video.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_stops_at_known_section_headers(): void
    {
        foreach (['CHAPTERS', 'TIMESTAMPS', 'LINKS', 'SPONSORS', 'FOLLOW', 'CONNECT',
                  'SUPPORT', 'AFFILIATE', 'MERCH', 'SOCIALS', 'PATREON'] as $header) {
            $desc   = "Good content here.\n{$header}\nhttps://example.com";
            $result = $this->processor->cleanDescription($desc);
            $this->assertSame('Good content here.', $result);
            $this->assertStringNotContainsString($header, $result);
        }
    }

    #[Test]
    public function cleanDescription_stops_at_section_headers_with_trailing_colon(): void
    {
        $desc = "Good content here.\nLINKS:\nhttps://example.com";
        $this->assertSame('Good content here.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_skips_subscribe_line_before_content_and_returns_body(): void
    {
        $desc = "Subscribe for more videos!\nThis episode covers deployment strategies.";
        $this->assertSame('This episode covers deployment strategies.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_stops_at_subscribe_line_once_content_has_started(): void
    {
        $desc = "This episode covers deployment strategies.\nSubscribe for more!";
        $this->assertSame('This episode covers deployment strategies.', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_looks_past_blank_lines_and_does_not_stop_on_them(): void
    {
        $desc = "First paragraph.\n\nSecond paragraph.\nhttps://link.com";
        $this->assertSame("First paragraph.\n\nSecond paragraph.", $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_returns_no_description_provided_when_nothing_useful_found(): void
    {
        $desc = "https://subscribe.com\n0:00 Intro\nLINKS:";
        $this->assertSame('No description provided', $this->processor->cleanDescription($desc));
    }

    #[Test]
    public function cleanDescription_returns_no_description_provided_for_empty_string(): void
    {
        $this->assertSame('No description provided', $this->processor->cleanDescription(''));
    }

    #[Test]
    public function cleanDescription_trims_trailing_blank_lines_from_collected_body(): void
    {
        $desc = "Real content.\n\n\nhttps://link.com";
        $this->assertSame('Real content.', $this->processor->cleanDescription($desc));
    }

    // =========================================================================
    // GROUP 1: Description mode
    // =========================================================================

    #[Test]
    public function description_mode_stores_cleaned_description(): void
    {
        $this->llmMock->shouldNotReceive('generateContent');
        Process::fake();

        $raw = "A great video about Laravel queues.\nhttps://sponsor.com\n0:00 Intro";

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
            ])),
        ]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('source_url', 'https://www.youtube.com/watch?v=vid1')->first();
        $this->assertStringContainsString('A great video about Laravel queues.', $summary->summary_html);
        $this->assertStringNotContainsString('sponsor.com', $summary->summary_html);
        $this->assertStringNotContainsString('0:00', $summary->summary_html);
        $this->assertStringStartsWith('<p>', $summary->summary_html);
    }

    #[Test]
    public function description_mode_stores_no_description_provided_when_description_is_empty(): void
    {
        $this->llmMock->shouldNotReceive('generateContent');
        Process::fake();

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', '', now()->subHour()->toIso8601String()),
            ])),
        ]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertStringContainsString('No description provided', $summary->summary_html);
        $this->assertTrue((bool) $summary->is_relevant);
    }

    #[Test]
    public function description_mode_stores_raw_description_in_source_description_column_unchanged(): void
    {
        $this->llmMock->shouldNotReceive('generateContent');
        Process::fake();

        $raw = "Good content.\nhttps://sponsor.com";

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
            ])),
        ]);

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertSame($raw, $summary->source_description);
        $this->assertStringNotContainsString('sponsor.com', $summary->summary_html);
    }

    // =========================================================================
    // GROUP 2: Summary mode
    // =========================================================================

    #[Test]
    public function summary_mode_fetches_transcript_calls_llm_and_stores_result(): void
    {
        $this->setYtProcessingMode('summary');

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', 'Desc.', now()->subHour()->toIso8601String()),
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
        $this->assertSame('<p>LLM summary.</p>', $summary->summary_html);
    }

    #[Test]
    public function summary_mode_falls_back_to_cleaned_description_when_transcript_unavailable(): void
    {
        $this->setYtProcessingMode('summary');

        $raw = "Really useful description content.\nhttps://sponsor.com\n0:00 Intro";

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', $raw, now()->subHour()->toIso8601String()),
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
        $this->assertStringContainsString('Really useful description content.', $summary->summary_html);
        $this->assertStringNotContainsString('sponsor.com', $summary->summary_html);
        $this->assertStringNotContainsString('0:00', $summary->summary_html);
    }

    #[Test]
    public function summary_mode_stores_unavailable_message_when_transcript_and_description_both_absent(): void
    {
        $this->setYtProcessingMode('summary');

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Video One', '', now()->subHour()->toIso8601String()),
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
        $this->assertStringContainsString('unavailable', strtolower($summary->summary_html));
    }

    // =========================================================================
    // GROUP 3: Search mode
    // =========================================================================

    #[Test]
    public function search_mode_tier1_matches_title_and_summarises(): void
    {
        $this->setYtProcessingMode('search', 'quantum computing');

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Intro to quantum computing', 'Desc.', now()->subHour()->toIso8601String()),
            ])),
        ]);

        Process::fake(['*get_transcript.py vid1*' => Process::result(output: json_encode(['transcript' => 'Transcript.']))]);
        $this->llmMock->shouldReceive('generateContent')->once()->andReturn('<p>Summary.</p>');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertTrue((bool) $summary->is_relevant);
    }

    #[Test]
    public function search_mode_tier3_not_relevant_stores_row_with_is_relevant_false(): void
    {
        $this->setYtProcessingMode('search', 'astrophysics');

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Cooking Pasta', 'How to make pasta.', now()->subHour()->toIso8601String()),
            ])),
        ]);

        Process::fake(['*get_transcript.py vid1*' => Process::result(output: json_encode(['transcript' => 'Pasta cooking transcript.']))]);
        $this->llmMock->shouldReceive('generateContent')->once()->andReturn('NOT_RELEVANT');

        $this->processor->process($this->listSource);

        $summary = DB::table('summaries')->where('list_source_id', $this->listSource->id)->first();
        $this->assertFalse((bool) $summary->is_relevant);
    }

    #[Test]
    public function search_mode_tier3_falls_back_to_cleaned_description_when_transcript_unavailable(): void
    {
        $this->setYtProcessingMode('search', 'astrophysics');

        $raw = "A deep dive into black holes and dark matter.\nhttps://sponsor.com";

        Http::fake([
            self::API_BASE => Http::response($this->ytPlaylistApiResponse([
                $this->ytVideoSnippet('vid1', 'Cooking Pasta', $raw, now()->subHour()->toIso8601String()),
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
        $this->assertStringContainsString('A deep dive into black holes', $summary->summary_html);
        $this->assertStringNotContainsString('sponsor.com', $summary->summary_html);
        $this->assertTrue((bool) $summary->is_relevant);
    }
}