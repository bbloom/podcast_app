<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/DigestRetentionServiceTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Publishing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use MediaPlatform\Digest\Publishing\Services\DigestRetentionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigestRetentionServiceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function retentionService(): DigestRetentionService
    {
        return new DigestRetentionService();
    }

    private function createIncludedSummary(
        int    $userId,
        int    $listId,
        string $includedDate,
        string $sourceUrl = 'https://example.com/article',
    ): int {
        $feedId = DB::table('text_based_rss_feeds')->insertGetId([
            'user_id'    => $userId,
            'title'      => 'Test Feed',
            'rss_url'    => 'https://example.com/feed-' . uniqid() . '.xml',
            'enabled'    => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listSourceId = DB::table('list_sources')
            ->where('list_id', $listId)
            ->where('sourceable_type', 'text_based_rss_feed')
            ->value('id');

        if (! $listSourceId) {
            $listSourceId = DB::table('list_sources')->insertGetId([
                'list_id'         => $listId,
                'sourceable_id'   => $feedId,
                'sourceable_type' => 'text_based_rss_feed',
                'enabled'         => true,
                'suspended'       => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return DB::table('summaries')->insertGetId([
            'user_id'               => $userId,
            'list_source_id'        => $listSourceId,
            'source_url'            => $sourceUrl . '-' . uniqid(),
            'source_title'          => 'Test Article',
            'processing_mode'       => 'description',
            'summary_html'          => '<p>Summary.</p>',
            'is_relevant'           => true,
            'included_in_digest'    => true,
            'included_in_digest_at' => $includedDate,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    // =========================================================================
    // GROUP 1: Static site — prunes published_digests
    // =========================================================================

    #[Test]
    public function prunes_oldest_published_digests_beyond_retention_count_for_static_site_list(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 3]);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);
        PublishedDigest::factory()->forList($list)->create(['slug' => 'day-4', 'digest_date' => '2026-04-13']);

        $this->retentionService()->pruneForList($list);

        $this->assertSame(3, PublishedDigest::where('list_id', $list->id)->count());
        $this->assertDatabaseMissing('published_digests', ['slug' => 'day-1']);
        $this->assertDatabaseHas('published_digests', ['slug' => 'day-2']);
        $this->assertDatabaseHas('published_digests', ['slug' => 'day-3']);
        $this->assertDatabaseHas('published_digests', ['slug' => 'day-4']);
    }

    #[Test]
    public function does_not_prune_published_digests_when_under_retention_count(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

        PublishedDigest::factory()->forList($list)->create(['slug' => 'only-one', 'digest_date' => '2026-04-18']);

        $this->retentionService()->pruneForList($list);

        $this->assertSame(1, PublishedDigest::where('list_id', $list->id)->count());
    }

    #[Test]
    public function does_not_prune_published_digests_belonging_to_other_lists(): void
    {
        $user  = User::factory()->create();
        $list1 = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 1]);
        $list2 = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

        PublishedDigest::factory()->forList($list1)->create(['slug' => 'l1-old', 'digest_date' => '2026-04-10']);
        PublishedDigest::factory()->forList($list1)->create(['slug' => 'l1-new', 'digest_date' => '2026-04-18']);
        PublishedDigest::factory()->forList($list2)->create(['slug' => 'l2-old', 'digest_date' => '2026-04-10']);

        $this->retentionService()->pruneForList($list1);

        $this->assertSame(1, PublishedDigest::where('list_id', $list1->id)->count());
        $this->assertDatabaseHas('published_digests', ['slug' => 'l1-new']);
        $this->assertDatabaseMissing('published_digests', ['slug' => 'l1-old']);
        $this->assertDatabaseHas('published_digests', ['slug' => 'l2-old']);
    }

    // =========================================================================
    // GROUP 2: Email — prunes included summaries
    // =========================================================================

    #[Test]
    public function prunes_oldest_included_summaries_beyond_retention_count_for_email_list(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 2,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

        $this->retentionService()->pruneForList($list);

        $this->assertSame(2, DB::table('summaries')->where('included_in_digest', true)->count());
    }

    #[Test]
    public function does_not_prune_included_summaries_when_under_retention_count(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 10,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');

        $this->retentionService()->pruneForList($list);

        $this->assertSame(2, DB::table('summaries')->where('included_in_digest', true)->count());
    }

    // =========================================================================
    // GROUP 3: Webpage — same pruning logic as email
    // =========================================================================

    #[Test]
    public function prunes_oldest_included_summaries_for_webpage_list(): void
    {
        $user = User::factory()->create();
        $dest = OutputDestination::factory()->forUser($user)->create();
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create([
            'retention_count' => 1,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');

        $this->retentionService()->pruneForList($list);

        $this->assertSame(1, DB::table('summaries')->where('included_in_digest', true)->count());
    }

    // =========================================================================
    // GROUP 4: Edge cases
    // =========================================================================

    #[Test]
    public function does_nothing_when_list_has_no_summaries_or_published_digests(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 5,
        ]);

        $this->retentionService()->pruneForList($list);

        $this->assertTrue(true); // did not throw
    }

    #[Test]
    public function does_nothing_when_retention_count_is_less_than_1(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 0,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');

        $this->retentionService()->pruneForList($list);

        $this->assertSame(1, DB::table('summaries')->where('included_in_digest', true)->count());
    }

    // =========================================================================
    // GROUP 5: Safety — never prunes pending or irrelevant summaries
    // =========================================================================

    #[Test]
    public function never_prunes_summaries_where_included_in_digest_is_false(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 1,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

        $listSourceId = DB::table('list_sources')->where('list_id', $list->id)->value('id');

        $pendingId = DB::table('summaries')->insertGetId([
            'user_id'            => $user->id,
            'list_source_id'     => $listSourceId,
            'source_url'         => 'https://example.com/pending-' . uniqid(),
            'source_title'       => 'Pending Article',
            'processing_mode'    => 'description',
            'summary_html'       => '<p>Pending.</p>',
            'is_relevant'        => true,
            'included_in_digest' => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->retentionService()->pruneForList($list);

        $this->assertDatabaseHas('summaries', ['id' => $pendingId]);
        $this->assertFalse((bool) DB::table('summaries')->find($pendingId)->included_in_digest);
    }

    #[Test]
    public function never_prunes_summaries_where_is_relevant_is_false(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 1,
        ]);

        $this->createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

        $listSourceId = DB::table('list_sources')->where('list_id', $list->id)->value('id');

        $irrelevantId = DB::table('summaries')->insertGetId([
            'user_id'               => $user->id,
            'list_source_id'        => $listSourceId,
            'source_url'            => 'https://example.com/irrelevant-' . uniqid(),
            'source_title'          => 'Irrelevant Article',
            'processing_mode'       => 'search',
            'summary_html'          => null,
            'is_relevant'           => false,
            'included_in_digest'    => false,
            'included_in_digest_at' => null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $this->retentionService()->pruneForList($list);

        $this->assertDatabaseHas('summaries', ['id' => $irrelevantId]);
    }

    #[Test]
    public function does_not_prune_summaries_belonging_to_other_lists(): void
    {
        $user  = User::factory()->create();
        $list1 = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 1,
        ]);
        $list2 = ListModel::factory()->forUser($user)->create([
            'output_type'     => OutputType::Email,
            'retention_count' => 10,
        ]);

        $this->createIncludedSummary($user->id, $list1->id, '2026-04-10 06:00:00');
        $this->createIncludedSummary($user->id, $list1->id, '2026-04-12 06:00:00');

        $list2SummaryId = $this->createIncludedSummary($user->id, $list2->id, '2026-04-10 06:00:00');

        $this->retentionService()->pruneForList($list1);

        $this->assertDatabaseHas('summaries', ['id' => $list2SummaryId]);
    }
}