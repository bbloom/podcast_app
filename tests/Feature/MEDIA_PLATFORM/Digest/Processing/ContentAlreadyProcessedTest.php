<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/ContentAlreadyProcessedTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentAlreadyProcessedTest extends TestCase
{
    use RefreshDatabase;

    private User      $user;
    private ListModel $list;
    private int       $listSourceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->list = ListModel::factory()->forUser($this->user)->create();

        DB::table('list_sources')->insert([
            'list_id'         => $this->list->id,
            'sourceable_id'   => 1,
            'sourceable_type' => 'youtube_channel',
            'enabled'         => true,
            'suspended'       => false,
            'processing_mode' => 'description',
            'search_terms'    => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->listSourceId = DB::table('list_sources')->value('id');
    }

    // =========================================================================
    // findBookmark
    // =========================================================================

    #[Test]
    public function returns_null_when_no_bookmark_exists(): void
    {
        $this->assertNull(ContentAlreadyProcessed::findBookmark($this->listSourceId));
    }

    #[Test]
    public function returns_the_bookmark_when_one_exists(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSourceId,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/item-1',
            'processed_at'   => now(),
        ]);

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSourceId);

        $this->assertNotNull($bookmark);
        $this->assertSame('https://example.com/item-1', $bookmark->source_url);
    }

    // =========================================================================
    // rotateBookmark
    // =========================================================================

    #[Test]
    public function inserts_a_bookmark_when_none_exists(): void
    {
        ContentAlreadyProcessed::rotateBookmark(
            listSourceId: $this->listSourceId,
            userId:       $this->user->id,
            sourceUrl:    'https://example.com/item-1',
        );

        $this->assertSame(1, ContentAlreadyProcessed::where('list_source_id', $this->listSourceId)->count());
        $this->assertSame(
            'https://example.com/item-1',
            ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url,
        );
    }

    #[Test]
    public function deletes_old_bookmark_and_inserts_new_one_on_rotation(): void
    {
        ContentAlreadyProcessed::create([
            'list_source_id' => $this->listSourceId,
            'user_id'        => $this->user->id,
            'source_url'     => 'https://example.com/item-1',
            'processed_at'   => now()->subDay(),
        ]);

        ContentAlreadyProcessed::rotateBookmark(
            listSourceId: $this->listSourceId,
            userId:       $this->user->id,
            sourceUrl:    'https://example.com/item-2',
        );

        $this->assertSame(1, ContentAlreadyProcessed::where('list_source_id', $this->listSourceId)->count());
        $this->assertSame(
            'https://example.com/item-2',
            ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url,
        );
    }

    #[Test]
    public function sets_processed_at_to_approximately_now_on_rotation(): void
    {
        $before = now()->subSecond();

        ContentAlreadyProcessed::rotateBookmark(
            listSourceId: $this->listSourceId,
            userId:       $this->user->id,
            sourceUrl:    'https://example.com/item-1',
        );

        $bookmark = ContentAlreadyProcessed::findBookmark($this->listSourceId);

        $this->assertTrue($bookmark->processed_at->gte($before));
    }

    #[Test]
    public function bookmarks_are_scoped_per_list_source_two_sources_have_independent_bookmarks(): void
    {
        DB::table('list_sources')->insert([
            'list_id'         => $this->list->id,
            'sourceable_id'   => 2,
            'sourceable_type' => 'youtube_channel',
            'enabled'         => true,
            'suspended'       => false,
            'processing_mode' => 'description',
            'search_terms'    => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $listSourceId2 = DB::table('list_sources')->orderByDesc('id')->value('id');

        ContentAlreadyProcessed::rotateBookmark($this->listSourceId, $this->user->id, 'https://example.com/source1-item');
        ContentAlreadyProcessed::rotateBookmark($listSourceId2,      $this->user->id, 'https://example.com/source2-item');

        $this->assertSame(
            'https://example.com/source1-item',
            ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url,
        );
        $this->assertSame(
            'https://example.com/source2-item',
            ContentAlreadyProcessed::findBookmark($listSourceId2)->source_url,
        );
        $this->assertSame(2, ContentAlreadyProcessed::count());
    }
}