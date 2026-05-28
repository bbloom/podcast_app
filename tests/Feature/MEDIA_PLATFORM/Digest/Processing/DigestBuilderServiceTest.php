<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/DigestBuilderServiceTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigestBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a minimal list with one list_source and a summary row.
     * Returns [$list, $listSourceId, $summaryId].
     */
    private function makeListWithSummary(User $user, array $summaryOverrides = []): array
    {
        $feedId = DB::table('text_based_rss_feeds')->insertGetId([
            'user_id'    => $user->id,
            'title'      => 'Test Feed',
            'rss_url'    => 'https://example.com/feed.xml',
            'enabled'    => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $list = ListModel::factory()->forUser($user)->create(['output_type' => 'email']);

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

    // =========================================================================
    // build() — happy path
    // =========================================================================

    #[Test]
    public function builds_a_digest_data_structure_for_a_list_with_pending_summaries(): void
    {
        $user = User::factory()->create();
        [$list] = $this->makeListWithSummary($user);

        $result = (new DigestBuilderService())->build($list);

        $this->assertNotNull($result);
        $this->assertSame($list->id, $result['list']->id);
        $this->assertSame(1, $result['total_items']);
        $this->assertSame(1, $result['source_count']);
        $this->assertCount(1, $result['groups']);

        $group = $result['groups']->first();
        $this->assertSame('Test Feed', $group['source_name']);
        $this->assertSame('text_based_rss_feed', $group['source_type']);
        $this->assertCount(1, $group['items']);
        $this->assertSame('Test Article', $group['items']->first()->source_title);
    }

    // =========================================================================
    // build() — returns null when nothing is pending
    // =========================================================================

    #[Test]
    public function returns_null_when_there_are_no_pending_summaries(): void
    {
        $user = User::factory()->create();
        [$list] = $this->makeListWithSummary($user, ['included_in_digest' => true]);

        $this->assertNull((new DigestBuilderService())->build($list));
    }

    #[Test]
    public function returns_null_when_all_summaries_are_irrelevant(): void
    {
        $user = User::factory()->create();
        [$list] = $this->makeListWithSummary($user, ['is_relevant' => false]);

        $this->assertNull((new DigestBuilderService())->build($list));
    }

    #[Test]
    public function excludes_summaries_with_null_summary_html(): void
    {
        $user = User::factory()->create();
        [$list] = $this->makeListWithSummary($user, ['summary_html' => null]);

        $this->assertNull((new DigestBuilderService())->build($list));
    }

    // =========================================================================
    // build() — groups multiple items from the same source together
    // =========================================================================

    #[Test]
    public function groups_multiple_summaries_from_the_same_source_into_one_group(): void
    {
        $user = User::factory()->create();
        [$list, $listSourceId] = $this->makeListWithSummary($user);

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

        $result = (new DigestBuilderService())->build($list);

        $this->assertSame(2, $result['total_items']);
        $this->assertSame(1, $result['source_count']);
        $this->assertCount(2, $result['groups']->first()['items']);
    }

    // =========================================================================
    // markAsIncluded()
    // =========================================================================

    #[Test]
    public function marks_summaries_as_included_after_build_and_markAsIncluded(): void
    {
        $user = User::factory()->create();
        [$list, , $summaryId] = $this->makeListWithSummary($user);

        $svc = new DigestBuilderService();
        $svc->build($list);
        $svc->markAsIncluded();

        $summary = DB::table('summaries')->find($summaryId);
        $this->assertTrue((bool) $summary->included_in_digest);
        $this->assertNotNull($summary->included_in_digest_at);
    }

    #[Test]
    public function does_nothing_if_markAsIncluded_is_called_without_a_prior_build(): void
    {
        (new DigestBuilderService())->markAsIncluded(); // should not throw
        $this->assertTrue(true);
    }

    #[Test]
    public function does_not_mark_summaries_as_included_if_markAsIncluded_is_never_called(): void
    {
        $user = User::factory()->create();
        [$list, , $summaryId] = $this->makeListWithSummary($user);

        $svc = new DigestBuilderService();
        $svc->build($list);
        // Deliberately NOT calling markAsIncluded

        $this->assertFalse((bool) DB::table('summaries')->find($summaryId)->included_in_digest);
    }

    // =========================================================================
    // buildSlug()
    // =========================================================================

    #[Test]
    public function builds_the_correct_slug_format_from_a_list_name(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create(['name' => 'Morning Tech']);
        $date = Carbon::parse('2026-03-13');

        $slug = (new DigestBuilderService())->buildSlug($list, $date);

        $this->assertSame('morning-tech-digest-2026-03-13', $slug);
    }

    #[Test]
    public function handles_special_characters_in_list_name_when_building_slug(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create(['name' => 'AI & Robotics Weekly']);
        $date = Carbon::parse('2026-03-13');

        $slug = (new DigestBuilderService())->buildSlug($list, $date);

        $this->assertStringContainsString('2026-03-13', $slug);
        $this->assertStringStartsWith('ai', $slug);
        $this->assertStringEndsWith('2026-03-13', $slug);
    }

    // =========================================================================
    // buildExcerpt()
    // =========================================================================

    #[Test]
    public function builds_a_grammatically_correct_excerpt_for_multiple_items_and_sources(): void
    {
        $user = User::factory()->create();
        [$list] = $this->makeListWithSummary($user);

        $svc        = new DigestBuilderService();
        $digestData = $svc->build($list);

        $this->assertSame('1 item from 1 source', $svc->buildExcerpt($digestData));
    }

    #[Test]
    public function uses_plural_forms_for_multiple_items_and_sources(): void
    {
        $digestData = [
            'total_items'  => 5,
            'source_count' => 3,
            'groups'       => collect([]),
            'list'         => null,
            'date'         => now(),
        ];

        $this->assertSame('5 items from 3 sources', (new DigestBuilderService())->buildExcerpt($digestData));
    }
}