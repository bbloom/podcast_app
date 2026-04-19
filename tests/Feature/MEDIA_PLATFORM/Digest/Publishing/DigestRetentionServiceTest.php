<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Publishing/DigestRetentionServiceTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use MediaPlatform\Digest\Publishing\Services\DigestRetentionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * DigestRetentionServiceTest
 *
 * Tests DigestRetentionService::pruneForList() for all output types.
 *
 * TEST GROUPS
 * ───────────
 *   1. Static site — prunes published_digests
 *   2. Email — prunes included summaries
 *   3. SFTP/Webpage — prunes included summaries (same as email)
 *   4. Edge cases — nothing to prune, retention_count = 0
 *   5. Safety — never prunes pending or irrelevant summaries
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

function retentionService(): DigestRetentionService
{
    return new DigestRetentionService();
}

/**
 * Create a list_source + summary row for a given list.
 * Returns the summary ID.
 */
function createIncludedSummary(
    int    $userId,
    int    $listId,
    string $includedDate,
    string $sourceUrl = 'https://example.com/article',
): int {
    // Ensure a list_source exists for this list
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

// =============================================================================
// GROUP 1: Static site — prunes published_digests
// =============================================================================

it('prunes oldest published_digests beyond retention_count for static site list', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 3]);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-1', 'digest_date' => '2026-04-10']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-2', 'digest_date' => '2026-04-11']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-3', 'digest_date' => '2026-04-12']);
    PublishedDigest::factory()->forList($list)->create(['slug' => 'day-4', 'digest_date' => '2026-04-13']);

    retentionService()->pruneForList($list);

    expect(PublishedDigest::where('list_id', $list->id)->count())->toBe(3);
    $this->assertDatabaseMissing('published_digests', ['slug' => 'day-1']);
    $this->assertDatabaseHas('published_digests', ['slug' => 'day-4']);
    $this->assertDatabaseHas('published_digests', ['slug' => 'day-3']);
    $this->assertDatabaseHas('published_digests', ['slug' => 'day-2']);
});

it('does not prune published_digests when under retention_count', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

    PublishedDigest::factory()->forList($list)->create(['slug' => 'only-one', 'digest_date' => '2026-04-18']);

    retentionService()->pruneForList($list);

    expect(PublishedDigest::where('list_id', $list->id)->count())->toBe(1);
});

it('does not prune published_digests belonging to other lists', function () {
    $user  = User::factory()->create();
    $list1 = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 1]);
    $list2 = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 10]);

    PublishedDigest::factory()->forList($list1)->create(['slug' => 'l1-old', 'digest_date' => '2026-04-10']);
    PublishedDigest::factory()->forList($list1)->create(['slug' => 'l1-new', 'digest_date' => '2026-04-18']);
    PublishedDigest::factory()->forList($list2)->create(['slug' => 'l2-old', 'digest_date' => '2026-04-10']);

    retentionService()->pruneForList($list1);

    // list1 should keep only the newest
    expect(PublishedDigest::where('list_id', $list1->id)->count())->toBe(1);
    $this->assertDatabaseHas('published_digests', ['slug' => 'l1-new']);
    $this->assertDatabaseMissing('published_digests', ['slug' => 'l1-old']);

    // list2 untouched
    $this->assertDatabaseHas('published_digests', ['slug' => 'l2-old']);
});

// =============================================================================
// GROUP 2: Email — prunes included summaries
// =============================================================================

it('prunes oldest included summaries beyond retention_count for email list', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 2,
    ]);

    // 3 digest runs: April 10, 11, 12
    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
    createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');
    createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

    retentionService()->pruneForList($list);

    // Should keep April 12 and 11, prune April 10
    $remaining = DB::table('summaries')
        ->where('included_in_digest', true)
        ->get();

    expect($remaining)->toHaveCount(2);
});

it('does not prune included summaries when under retention_count', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 10,
    ]);

    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
    createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');

    retentionService()->pruneForList($list);

    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(2);
});

// =============================================================================
// GROUP 3: Webpage — same pruning logic as email
// =============================================================================

it('prunes oldest included summaries for webpage list', function () {
    $user = User::factory()->create();
    $dest = \MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination::factory()
        ->forUser($user)->create();
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create([
        'retention_count' => 1,
    ]);

    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
    createIncludedSummary($user->id, $list->id, '2026-04-11 06:00:00');

    retentionService()->pruneForList($list);

    // Keep only the newest run date
    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(1);
});

// =============================================================================
// GROUP 4: Edge cases
// =============================================================================

it('does nothing when list has no summaries or published digests', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 5,
    ]);

    // Should not throw
    retentionService()->pruneForList($list);

    expect(true)->toBeTrue();
});

it('does nothing when retention_count is less than 1', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 0,
    ]);

    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');

    retentionService()->pruneForList($list);

    // Nothing pruned because retention_count < 1 triggers early return
    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(1);
});

// =============================================================================
// GROUP 5: Safety — never prunes pending or irrelevant summaries
// =============================================================================

it('never prunes summaries where included_in_digest is false', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 1,
    ]);

    // Create an included summary (old, should be pruned)
    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
    // Create an included summary (new, should be kept)
    createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

    // Create a pending summary (not included — must survive)
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

    retentionService()->pruneForList($list);

    // Pending summary must survive
    $this->assertDatabaseHas('summaries', ['id' => $pendingId]);
    expect(DB::table('summaries')->find($pendingId)->included_in_digest)->toBe(false);
});

it('never prunes summaries where is_relevant is false', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 1,
    ]);

    createIncludedSummary($user->id, $list->id, '2026-04-10 06:00:00');
    createIncludedSummary($user->id, $list->id, '2026-04-12 06:00:00');

    // Create an irrelevant summary (search mode non-match — must survive)
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

    retentionService()->pruneForList($list);

    // Irrelevant summary must survive
    $this->assertDatabaseHas('summaries', ['id' => $irrelevantId]);
});

it('does not prune summaries belonging to other lists', function () {
    $user  = User::factory()->create();
    $list1 = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 1,
    ]);
    $list2 = ListModel::factory()->forUser($user)->create([
        'output_type'     => OutputType::Email,
        'retention_count' => 10,
    ]);

    // list1: 2 runs, retention = 1 → old should be pruned
    createIncludedSummary($user->id, $list1->id, '2026-04-10 06:00:00');
    createIncludedSummary($user->id, $list1->id, '2026-04-12 06:00:00');

    // list2: 1 run — must not be affected
    $list2SummaryId = createIncludedSummary($user->id, $list2->id, '2026-04-10 06:00:00');

    retentionService()->pruneForList($list1);

    // list2's summary must survive
    $this->assertDatabaseHas('summaries', ['id' => $list2SummaryId]);
});