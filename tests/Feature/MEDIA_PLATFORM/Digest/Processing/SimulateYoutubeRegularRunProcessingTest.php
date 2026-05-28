<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SimulateYoutubeRegularRunProcessingTest.php

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

class SimulateYoutubeRegularRunProcessingTest extends TestCase
{
    use RefreshDatabase;

    private const API_BASE = 'www.googleapis.com/youtube/v3/playlistItems*';

    private User                    $user;
    private ListModel               $list;
    private YoutubeChannel          $channel;
    private object                  $listSource;
    private LlmService              $llmMock;
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
    // TC8
    // =========================================================================

    #[Test]
    public function TC8_routes_to_regular_run_when_bookmark_exists(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(7));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subDays(7),
        ]);

        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(6, $stats['processed']);
    }

    // =========================================================================
    // TC9
    // =========================================================================

    #[Test]
    public function TC9_stops_immediately_at_bookmark_url_when_first_item_is_the_bookmark(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(0, DB::table('summaries')->where('list_source_id', $this->listSource->id)->count());
    }

    // =========================================================================
    // TC10
    // =========================================================================

    #[Test]
    public function TC10_bookmark_is_unchanged_after_a_zero_result_regular_run(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
        $this->processor->process($this->listSource);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $this->assertSame($bookmarkUrl, $bookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC11
    // =========================================================================

    #[Test]
    public function TC11_stats_are_all_zero_when_nothing_new_to_process(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0,  $stats['processed']);
        $this->assertSame(0,  $stats['skipped']);
        $this->assertSame(0,  $stats['errors']);
        $this->assertSame(50, $stats['fetched']);
    }

    // =========================================================================
    // TC12
    // =========================================================================

    #[Test]
    public function TC12_second_zero_result_run_is_identical_to_first_zero_result_run(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subHour(),
        ]);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
        $stats1 = $this->processor->process($this->listSource);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
        $stats2 = $this->processor->process($this->listSource);

        $this->assertSame($stats1, $stats2);
        $this->assertSame(1, ContentAlreadyProcessed::count());
        $this->assertSame($bookmarkUrl, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }

    // =========================================================================
    // TC13–18
    // =========================================================================

    #[Test]
    public function TC13_18_processes_exactly_5_new_items_when_5_are_prepended_to_the_feed(): void
    {
        $originalFeedResponse = FakeYoutubePlaylistBuilder::build(50, now()->subDays(10), 1, 'vid');
        $originalBookmarkUrl  = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'vid'));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $originalBookmarkUrl,
            'processed_at'   => now()->subDays(10),
        ]);

        $newItemsResponse = FakeYoutubePlaylistBuilder::build(5, now(), 1, 'new');
        $combinedResponse = [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake',
            'pageInfo' => ['totalResults' => 55, 'resultsPerPage' => 50],
            'items'    => array_merge($newItemsResponse['items'], $originalFeedResponse['items']),
        ];

        Http::fake([self::API_BASE => Http::response($combinedResponse)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(5, $stats['processed']);
        $this->assertDatabaseMissing('summaries', ['source_url' => $originalBookmarkUrl]);

        $afterBookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(2, 'vid'));
        $this->assertDatabaseMissing('summaries', ['source_url' => $afterBookmarkUrl]);

        $newBookmark        = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $expectedNewBookmark = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
        $this->assertSame($expectedNewBookmark, $newBookmark->source_url);
        $this->assertSame(1, ContentAlreadyProcessed::count());
        $this->assertSame(5, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }

    // =========================================================================
    // TC19–21
    // =========================================================================

    #[Test]
    public function TC19_21_fallback_stop_when_bookmark_url_is_gone_from_feed(): void
    {
        $bookmarkTime    = now()->subDays(3);
        $deletedVideoUrl = 'https://www.youtube.com/watch?v=deleted_video';

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $deletedVideoUrl,
            'processed_at'   => $bookmarkTime,
        ]);

        $newItems = FakeYoutubePlaylistBuilder::build(3,  now(),                   1, 'new');
        $oldItems = FakeYoutubePlaylistBuilder::build(50, $bookmarkTime->subDay(), 1, 'old');

        $combinedResponse = [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake',
            'pageInfo' => ['totalResults' => 53, 'resultsPerPage' => 50],
            'items'    => array_merge($newItems['items'], $oldItems['items']),
        ];

        Http::fake([self::API_BASE => Http::response($combinedResponse)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(3, $stats['processed']);

        $newBookmark = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
        $this->assertSame($expectedUrl, $newBookmark->source_url);
    }

    // =========================================================================
    // TC22–23
    // =========================================================================

    #[Test]
    public function TC22_23_processes_items_with_no_published_at_and_sets_bookmark_correctly(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subDays(2),
        ]);

        $noDateItem = [
            'kind'           => 'youtube#playlistItem',
            'etag'           => 'fake-no-date',
            'snippet'        => [
                'title'       => 'No Date Video',
                'description' => 'This video has no published date.',
                'resourceId'  => ['kind' => 'youtube#video', 'videoId' => 'no_date_vid'],
            ],
            'contentDetails' => ['videoId' => 'no_date_vid'],
        ];

        $originalFeed     = FakeYoutubePlaylistBuilder::build(50, now()->subDay(), 1);
        $combinedResponse = [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake',
            'pageInfo' => ['totalResults' => 51, 'resultsPerPage' => 50],
            'items'    => array_merge([$noDateItem], $originalFeed['items']),
        ];

        Http::fake([self::API_BASE => Http::response($combinedResponse)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertGreaterThanOrEqual(1, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
    }

    // =========================================================================
    // TC24
    // =========================================================================

    #[Test]
    public function TC24_feed_fetch_failure_leaves_bookmark_unchanged_and_records_error(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subDay(),
        ]);

        Http::fake([self::API_BASE => Http::response('Server Error', 500)]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(1, $stats['errors']);
        $this->assertSame(0, $stats['processed']);
        $this->assertSame($bookmarkUrl, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }

    // =========================================================================
    // TC25
    // =========================================================================

    #[Test]
    public function TC25_zero_item_feed_leaves_bookmark_unchanged_with_no_errors(): void
    {
        $bookmarkUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));

        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => $bookmarkUrl,
            'processed_at'   => now()->subDay(),
        ]);

        Http::fake([
            self::API_BASE => Http::response(['kind' => 'youtube#playlistItemListResponse', 'items' => []]),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $stats['fetched']);
        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame($bookmarkUrl, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }

    // =========================================================================
    // TC26
    // =========================================================================

    #[Test]
    public function TC26_all_50_items_are_new_all_processed_bookmark_set_to_item_1(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSource->id,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://www.youtube.com/watch?v=old_video_not_in_feed',
            'processed_at'   => now()->subDays(60),
        ]);

        Http::fake([
            self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1)),
        ]);

        $stats = $this->processor->process($this->listSource);

        $this->assertSame(50, $stats['processed']);

        $bookmark    = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $expectedUrl = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1));
        $this->assertSame($expectedUrl, $bookmark->source_url);
    }

    // =========================================================================
    // TC27
    // =========================================================================

    #[Test]
    public function TC27_single_item_feed_first_run_processes_it_regular_run_stops_immediately(): void
    {
        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(1, now(), 1))]);
        $firstRunStats = $this->processor->process($this->listSource);
        $this->assertSame(1, $firstRunStats['processed']);
        $this->assertNotNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(1, now(), 1))]);
        $regularRunStats = $this->processor->process($this->listSource);
        $this->assertSame(0, $regularRunStats['processed']);
        $this->assertSame(1, ContentAlreadyProcessed::count());
    }

    // =========================================================================
    // TC28
    // =========================================================================

    #[Test]
    public function TC28_two_list_sources_on_same_channel_have_independent_bookmarks(): void
    {
        $list2 = ListModel::factory()->forUser($this->user)->create();

        DB::table('list_sources')->insert([
            'list_id'         => $list2->id,
            'sourceable_id'   => $this->channel->id,
            'sourceable_type' => 'youtube_channel',
            'enabled'         => true,
            'suspended'       => false,
            'processing_mode' => 'description',
            'search_terms'    => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $listSource2 = DB::table('list_sources')->where('list_id', $list2->id)->first();

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
        $this->processor->process($this->listSource);

        Http::fake([self::API_BASE => Http::response(FakeYoutubePlaylistBuilder::build(50, now(), 1))]);
        $this->processor->process($listSource2);

        $this->assertSame(2, ContentAlreadyProcessed::count());

        $bookmark1 = ContentAlreadyProcessed::findBookmark($this->listSource->id);
        $bookmark2 = ContentAlreadyProcessed::findBookmark($listSource2->id);

        $this->assertSame($this->listSource->id, $bookmark1->list_source_id);
        $this->assertSame($listSource2->id, $bookmark2->list_source_id);
        $this->assertSame($bookmark1->source_url, $bookmark2->source_url);
    }

    // =========================================================================
    // TC30–34
    // =========================================================================

    #[Test]
    public function TC30_34_full_end_to_end_chain_first_run_two_zero_runs_new_items_zero_run(): void
    {
        $feed50      = FakeYoutubePlaylistBuilder::build(50, now()->subDays(1), 1);
        $newItemsR4  = FakeYoutubePlaylistBuilder::build(5, now()->addDays(10), 1, 'new');
        $feed55      = [
            'kind'     => 'youtube#playlistItemListResponse',
            'etag'     => 'fake',
            'pageInfo' => ['totalResults' => 55, 'resultsPerPage' => 50],
            'items'    => array_merge($newItemsR4['items'], $feed50['items']),
        ];

        Http::fakeSequence()
            ->push($feed50)
            ->push($feed50)
            ->push($feed50)
            ->push($feed55)
            ->push($feed55);

        // Run 1: First run
        $run1Stats = $this->processor->process($this->listSource);

        $this->assertGreaterThanOrEqual(1, $run1Stats['processed'], 'Run 1: should process at least 1 item');
        $this->assertGreaterThanOrEqual(1, $run1Stats['skipped'],   'Run 1: should skip at least 1 item');
        $this->assertNotNull(ContentAlreadyProcessed::findBookmark($this->listSource->id));

        $bookmarkAfterRun1 = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;

        // Run 2: THE CRITICAL CHECK
        $run2Stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $run2Stats['processed'], 'Run 2: THE CRITICAL CHECK — must process 0, not 43');
        $this->assertSame(0, $run2Stats['errors'],     'Run 2: no errors');
        $this->assertSame($bookmarkAfterRun1, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);

        // Run 3
        $run3Stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $run3Stats['processed'], 'Run 3: should still process 0');
        $this->assertSame($bookmarkAfterRun1, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);

        // Run 4: 5 new items
        $run4Stats = $this->processor->process($this->listSource);

        $this->assertSame(5, $run4Stats['processed'], 'Run 4: should process exactly 5 new items');
        $this->assertSame(0, $run4Stats['errors'],     'Run 4: no errors');

        $bookmarkAfterRun4   = ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url;
        $expectedNewBookmark = FakeYoutubePlaylistBuilder::sourceUrl(FakeYoutubePlaylistBuilder::videoId(1, 'new'));
        $this->assertNotSame($bookmarkAfterRun1, $bookmarkAfterRun4, 'Run 4: bookmark must have rotated');
        $this->assertSame($expectedNewBookmark, $bookmarkAfterRun4);

        // Run 5
        $run5Stats = $this->processor->process($this->listSource);

        $this->assertSame(0, $run5Stats['processed'], 'Run 5: should process 0 after new items absorbed');
        $this->assertSame($bookmarkAfterRun4, ContentAlreadyProcessed::findBookmark($this->listSource->id)->source_url);
    }
}