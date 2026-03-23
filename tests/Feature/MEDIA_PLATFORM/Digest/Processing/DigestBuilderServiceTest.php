<?php

// tests/Feature/Processing/DigestBuilderServiceTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ============================================================================
// Helpers
// ============================================================================

/**
 * Create a minimal list with one list_source and insert a summary row directly
 * via DB so we are not coupled to any specific processor implementation.
 *
 * Returns [$list, $listSourceId, $summaryId]
 */
function makeListWithSummary(User $user, array $summaryOverrides = []): array
{
    // Create a text_based_rss_feed source record so the name lookup works.
    $feedId = DB::table('text_based_rss_feeds')->insertGetId([
        'user_id'   => $user->id,
        'title'     => 'Test Feed',
        'rss_url'   => 'https://example.com/feed.xml',
        'enabled'   => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $list = ListModel::factory()->forUser($user)->create([
        'output_type' => 'email',
    ]);

    $listSourceId = DB::table('list_sources')->insertGetId([
        'list_id'         => $list->id,
        'sourceable_id'   => $feedId,
        'sourceable_type' => 'text_based_rss_feed',
        'enabled'         => true,
        'suspended'       => false,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $summaryId = DB::table('summaries')->insertGetId(array_merge([
        'user_id'               => $user->id,
        'list_source_id'        => $listSourceId,
        'source_url'            => 'https://example.com/article-1',
        'source_title'          => 'Test Article',
        'source_description'    => 'A test article.',
        'source_published_at'   => now()->subHour(),
        'processing_mode'       => 'description',
        'summary_html'          => '<p>Test summary.</p>',
        'is_relevant'           => true,
        'included_in_digest'    => false,
        'included_in_digest_at' => null,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $summaryOverrides));

    return [$list, $listSourceId, $summaryId];
}

// ============================================================================
// build() — happy path
// ============================================================================

it('builds a digest data structure for a list with pending summaries', function () {
    $user = User::factory()->create();
    [$list, $listSourceId, $summaryId] = makeListWithSummary($user);

    $service = new DigestBuilderService();
    $result  = $service->build($list);

    expect($result)->not->toBeNull();
    expect($result['list']->id)->toBe($list->id);
    expect($result['total_items'])->toBe(1);
    expect($result['source_count'])->toBe(1);
    expect($result['groups'])->toHaveCount(1);

    $group = $result['groups']->first();
    expect($group['source_name'])->toBe('Test Feed');
    expect($group['source_type'])->toBe('text_based_rss_feed');
    expect($group['items'])->toHaveCount(1);
    expect($group['items']->first()->source_title)->toBe('Test Article');
});

// ============================================================================
// build() — returns null when nothing is pending
// ============================================================================

it('returns null when there are no pending summaries', function () {
    $user = User::factory()->create();
    [$list] = makeListWithSummary($user, ['included_in_digest' => true]);

    $service = new DigestBuilderService();
    $result  = $service->build($list);

    expect($result)->toBeNull();
});

it('returns null when all summaries are irrelevant', function () {
    $user = User::factory()->create();
    [$list] = makeListWithSummary($user, ['is_relevant' => false]);

    $service = new DigestBuilderService();
    $result  = $service->build($list);

    expect($result)->toBeNull();
});

it('excludes summaries with null summary_html', function () {
    $user = User::factory()->create();
    [$list] = makeListWithSummary($user, ['summary_html' => null]);

    $service = new DigestBuilderService();
    $result  = $service->build($list);

    expect($result)->toBeNull();
});

// ============================================================================
// build() — groups multiple items from the same source together
// ============================================================================

it('groups multiple summaries from the same source into one group', function () {
    $user   = User::factory()->create();
    [$list, $listSourceId] = makeListWithSummary($user);

    // Insert a second summary for the same list_source
    DB::table('summaries')->insert([
        'user_id'            => $user->id,
        'list_source_id'     => $listSourceId,
        'source_url'         => 'https://example.com/article-2',
        'source_title'       => 'Second Article',
        'processing_mode'    => 'description',
        'summary_html'       => '<p>Second.</p>',
        'is_relevant'        => true,
        'included_in_digest' => false,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    $service = new DigestBuilderService();
    $result  = $service->build($list);

    expect($result['total_items'])->toBe(2);
    expect($result['source_count'])->toBe(1);       // still one group
    expect($result['groups']->first()['items'])->toHaveCount(2);
});

// ============================================================================
// markAsIncluded()
// ============================================================================

it('marks summaries as included after build and markAsIncluded', function () {
    $user = User::factory()->create();
    [$list, , $summaryId] = makeListWithSummary($user);

    $service = new DigestBuilderService();
    $service->build($list);
    $service->markAsIncluded();

    $summary = DB::table('summaries')->find($summaryId);
    expect($summary->included_in_digest)->toBe(true);
    expect($summary->included_in_digest_at)->not->toBeNull();
});

it('does nothing if markAsIncluded is called without a prior build', function () {
    // Should not throw; collected IDs array is empty so update is a no-op
    $service = new DigestBuilderService();
    $service->markAsIncluded(); // should not throw

    expect(true)->toBeTrue(); // reached without exception
});

it('does not mark summaries as included if markAsIncluded is never called', function () {
    $user = User::factory()->create();
    [$list, , $summaryId] = makeListWithSummary($user);

    $service = new DigestBuilderService();
    $service->build($list);
    // Deliberately NOT calling markAsIncluded

    $summary = DB::table('summaries')->find($summaryId);
    expect($summary->included_in_digest)->toBe(false);
});

// ============================================================================
// buildSlug()
// ============================================================================

it('builds the correct slug format from a list name', function () {
    $user    = User::factory()->create();
    // Use a list name without "digest" in it to get a clean, predictable slug.
    // The buildSlug() method appends "-digest-{date}" to the slugified name.
    $list    = ListModel::factory()->forUser($user)->create(['name' => 'Morning Tech']);
    $service = new DigestBuilderService();
    $date    = \Illuminate\Support\Carbon::parse('2026-03-13');

    $slug = $service->buildSlug($list, $date);

    expect($slug)->toBe('morning-tech-digest-2026-03-13');
});

it('handles special characters in list name when building slug', function () {
    $user    = User::factory()->create();
    $list    = ListModel::factory()->forUser($user)->create(['name' => 'AI & Robotics Weekly']);
    $service = new DigestBuilderService();
    $date    = \Illuminate\Support\Carbon::parse('2026-03-13');

    $slug = $service->buildSlug($list, $date);

    // & and space both become hyphens; consecutive hyphens collapse
    expect($slug)->toContain('2026-03-13');
    expect($slug)->toStartWith('ai');
    expect($slug)->toEndWith('2026-03-13');
});

// ============================================================================
// buildExcerpt()
// ============================================================================

it('builds a grammatically correct excerpt for multiple items and sources', function () {
    $user = User::factory()->create();
    [$list] = makeListWithSummary($user);

    $service    = new DigestBuilderService();
    $digestData = $service->build($list);

    $excerpt = $service->buildExcerpt($digestData);
    expect($excerpt)->toBe('1 item from 1 source');
});

it('uses plural forms for multiple items and sources', function () {
    $service    = new DigestBuilderService();
    $digestData = [
        'total_items'  => 5,
        'source_count' => 3,
        'groups'       => collect([]),
        'list'         => null,
        'date'         => now(),
    ];

    $excerpt = $service->buildExcerpt($digestData);
    expect($excerpt)->toBe('5 items from 3 sources');
});