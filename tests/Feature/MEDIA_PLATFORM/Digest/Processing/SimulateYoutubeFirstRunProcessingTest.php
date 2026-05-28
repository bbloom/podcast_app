<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SimulateYoutubeFirstRunProcessingTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use MediaPlatform\Digest\Processing\Services\LlmService;
use MediaPlatform\Digest\Processing\Youtube\Services\YoutubeContentProcessor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeYoutubePlaylistBuilder;
use Tests\TestCase;

class SimulateYoutubeFirstRunProcessingTest extends TestCase
{
    use RefreshDatabase;

    private const API_BASE = 'www.googleapis.com/youtube/v3/playlistItems*';

    private User                   $user;
    private ListModel              $list;
    private YoutubeChannel         $channel;
    private object                 $listSource;
    private LlmService             $llmMock;
    private YoutubeContentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['processing.first_run_lookback_days' => 7]);

        $this->user    = User::factory()->create();
        $this->list    = ListModel::factory()->forUser($this->user)->create();
        $this->channel = YoutubeChannel::factory()->forUser($this->user)->create();

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
            ->first();

        $this->llmMock = mock(LlmService::class);
        app()->instance(LlmService::class, $this->llmMock);
        $this->processor = new YoutubeContentProcessor($this->llmMock);

        Process::fake();
    }

    // =========================================================================
    // TC1
    // =========================================================================

    #[Test]
    public function TC1_routes_to_first_run_when_no_bookmark_exists(): void
    {
        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));

        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(7,  $stats['processed']);
        $this->assertSame(43, $stats['skipped']);
    }

    // =========================================================================
    // TC2
    // =========================================================================

    #[Test]
    public function TC2_processes_only_items_within_the_lookback_window_on_first_run(): void
    {
        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(50, $stats['fetched']);
        $this->assertSame(7,  $stats['processed']);
        $this->assertSame(0,  $stats['errors']);
        $this->assertSame(7, DB::table('summaries')->where('list_source_id', $this->listSource->id)->count());
    }

    // =========================================================================
    // TC3
    // =========================================================================

    #[Test]
    public function TC3_skips_items_older_than_the_lookback_window_on_first_run(): void
    {
        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(43, $stats['skipped']);

        $this->assertDatabaseMissing('summaries', [
            'source_url' => FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(8)),
        ]);
        $this->assertDatabaseMissing('summaries', [
            'source_url' => FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(50)),
        ]);
    }

    // =========================================================================
    // TC4
    // =========================================================================

    #[Test]
    public function TC4_inserts_bookmark_pointing_to_the_newest_processed_item_after_first_run(): void
    {
        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $this->processor->process($this->listSource);

        $bookmark    = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        $this->assertNotNull($bookmark);
        $this->assertSame($expectedUrl, $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC5
    // =========================================================================

    #[Test]
    public function TC5_inserts_no_bookmark_when_no_items_fall_within_the_lookback_window(): void
    {
        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now()->subDays(10), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0,  $stats['processed']);
        $this->assertSame(50, $stats['skipped']);
        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));
    }

    // =========================================================================
    // TC6
    // =========================================================================

    #[Test]
    public function TC6_processes_items_with_no_published_at_date_on_first_run(): void
    {
        $apiResponse = [
            'items' => [[
                'snippet' => [
                    'title'       => 'No Date Video',
                    'description' => 'No published date.',
                    'resourceId'  => ['kind' => 'youtube#video', 'videoId' => 'no_date_vid'],
                ],
                'contentDetails' => [
                    'videoId' => 'no_date_vid',
                ],
            ]],
        ];

        Http::fake([self::API_BASE => Http::response($apiResponse)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['errors']);
    }

    // =========================================================================
    // TC7
    // =========================================================================

    #[Test]
    public function TC7_stats_counts_are_accurate_on_first_run(): void
    {
        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(50, $stats['fetched']);
        $this->assertSame(7,  $stats['processed']);
        $this->assertSame(43, $stats['skipped']);
        $this->assertSame(0,  $stats['errors']);
        $this->assertSame(7, DB::table('summaries')->where('list_source_id', $this->listSource->id)->count());
    }
}