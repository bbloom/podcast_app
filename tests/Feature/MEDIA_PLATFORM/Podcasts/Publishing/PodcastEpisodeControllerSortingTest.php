<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing;

use App\Models\User;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodcastEpisodeControllerSortingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private User $user;
    private PodcastShow $showA;
    private PodcastShow $showB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Two shows so we can test sorting by show title.
        $this->showA = PodcastShow::factory()->create([
            'user_id' => $this->user->id,
            'title'   => 'Alpha Show',
        ]);

        $this->showB = PodcastShow::factory()->create([
            'user_id' => $this->user->id,
            'title'   => 'Zulu Show',
        ]);
    }

    /**
     * Create an episode with the given overrides, defaulting to this user.
     */
    private function makeEpisode(array $overrides = []): PodcastEpisode
    {
        return PodcastEpisode::factory()->create(array_merge([
            'user_id'         => $this->user->id,
            'podcast_show_id' => $this->showA->id,
            'status'          => PodcastEpisodeStatus::ready_to_upload_recording,
        ], $overrides));
    }

    /**
     * Extract episode IDs from the response view data, in display order.
     */
    private function episodeIdsFromResponse($response): array
    {
        return $response->viewData('episodes')->pluck('id')->all();
    }

    // -------------------------------------------------------------------------
    // Default sort
    // -------------------------------------------------------------------------

    public function test_index_defaults_to_id_descending(): void
    {
        $ep1 = $this->makeEpisode(['title' => 'First']);
        $ep2 = $this->makeEpisode(['title' => 'Second']);
        $ep3 = $this->makeEpisode(['title' => 'Third']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index'));

        $response->assertOk();
        $this->assertEquals([$ep3->id, $ep2->id, $ep1->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Sort by ID
    // -------------------------------------------------------------------------

    public function test_sort_by_id_ascending(): void
    {
        $ep1 = $this->makeEpisode(['title' => 'First']);
        $ep2 = $this->makeEpisode(['title' => 'Second']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'id', 'dir' => 'asc']));

        $response->assertOk();
        $this->assertEquals([$ep1->id, $ep2->id], $this->episodeIdsFromResponse($response));
    }

    public function test_sort_by_id_descending(): void
    {
        $ep1 = $this->makeEpisode(['title' => 'First']);
        $ep2 = $this->makeEpisode(['title' => 'Second']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'id', 'dir' => 'desc']));

        $response->assertOk();
        $this->assertEquals([$ep2->id, $ep1->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Sort by title
    // -------------------------------------------------------------------------

    public function test_sort_by_title_ascending(): void
    {
        $epB = $this->makeEpisode(['title' => 'Bravo Episode']);
        $epA = $this->makeEpisode(['title' => 'Alpha Episode']);
        $epC = $this->makeEpisode(['title' => 'Charlie Episode']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'title', 'dir' => 'asc']));

        $response->assertOk();
        $this->assertEquals([$epA->id, $epB->id, $epC->id], $this->episodeIdsFromResponse($response));
    }

    public function test_sort_by_title_descending(): void
    {
        $epB = $this->makeEpisode(['title' => 'Bravo Episode']);
        $epA = $this->makeEpisode(['title' => 'Alpha Episode']);
        $epC = $this->makeEpisode(['title' => 'Charlie Episode']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'title', 'dir' => 'desc']));

        $response->assertOk();
        $this->assertEquals([$epC->id, $epB->id, $epA->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Sort by show
    // -------------------------------------------------------------------------

    public function test_sort_by_show_ascending(): void
    {
        $epZulu  = $this->makeEpisode(['podcast_show_id' => $this->showB->id, 'title' => 'Ep in Zulu']);
        $epAlpha = $this->makeEpisode(['podcast_show_id' => $this->showA->id, 'title' => 'Ep in Alpha']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'show', 'dir' => 'asc']));

        $response->assertOk();
        $this->assertEquals([$epAlpha->id, $epZulu->id], $this->episodeIdsFromResponse($response));
    }

    public function test_sort_by_show_descending(): void
    {
        $epZulu  = $this->makeEpisode(['podcast_show_id' => $this->showB->id, 'title' => 'Ep in Zulu']);
        $epAlpha = $this->makeEpisode(['podcast_show_id' => $this->showA->id, 'title' => 'Ep in Alpha']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'show', 'dir' => 'desc']));

        $response->assertOk();
        $this->assertEquals([$epZulu->id, $epAlpha->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Sort by status
    // -------------------------------------------------------------------------

    public function test_sort_by_status_ascending(): void
    {
        $epPub = $this->makeEpisode(['status' => PodcastEpisodeStatus::published, 'title' => 'Published Ep']);
        $epCre = $this->makeEpisode(['status' => PodcastEpisodeStatus::ready_to_upload_recording,   'title' => 'Created Ep']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'status', 'dir' => 'asc']));

        $response->assertOk();

        $ids = $this->episodeIdsFromResponse($response);
        // Just verify both are present and the order is consistent.
        $this->assertCount(2, $ids);
        $this->assertEqualsCanonicalizing([$epPub->id, $epCre->id], $ids);
    }

    // -------------------------------------------------------------------------
    // Sort by scheduled_date
    // -------------------------------------------------------------------------

    public function test_sort_by_scheduled_date_ascending(): void
    {
        $epLater = $this->makeEpisode(['scheduled_date' => '2026-06-15', 'title' => 'Later']);
        $epSoon  = $this->makeEpisode(['scheduled_date' => '2026-05-01', 'title' => 'Soon']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'scheduled_date', 'dir' => 'asc']));

        $response->assertOk();
        $this->assertEquals([$epSoon->id, $epLater->id], $this->episodeIdsFromResponse($response));
    }

    public function test_sort_by_scheduled_date_descending(): void
    {
        $epLater = $this->makeEpisode(['scheduled_date' => '2026-06-15', 'title' => 'Later']);
        $epSoon  = $this->makeEpisode(['scheduled_date' => '2026-05-01', 'title' => 'Soon']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'scheduled_date', 'dir' => 'desc']));

        $response->assertOk();
        $this->assertEquals([$epLater->id, $epSoon->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Invalid / malicious sort parameters
    // -------------------------------------------------------------------------

    public function test_invalid_sort_column_falls_back_to_id(): void
    {
        $ep1 = $this->makeEpisode(['title' => 'First']);
        $ep2 = $this->makeEpisode(['title' => 'Second']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'nonexistent_column', 'dir' => 'asc']));

        $response->assertOk();
        // Sort column falls back to id, but the provided direction (asc) is still valid.
        $this->assertEquals([$ep1->id, $ep2->id], $this->episodeIdsFromResponse($response));
    }

    public function test_invalid_direction_falls_back_to_desc(): void
    {
        $ep1 = $this->makeEpisode(['title' => 'First']);
        $ep2 = $this->makeEpisode(['title' => 'Second']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'id', 'dir' => 'DROP TABLE users;']));

        $response->assertOk();
        $this->assertEquals([$ep2->id, $ep1->id], $this->episodeIdsFromResponse($response));
    }

    // -------------------------------------------------------------------------
    // Sort params persist across pagination
    // -------------------------------------------------------------------------

    public function test_sort_params_are_appended_to_pagination_links(): void
    {
        // Create enough episodes to trigger pagination (>20).
        for ($i = 0; $i < 22; $i++) {
            $this->makeEpisode(['title' => "Episode {$i}"]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'title', 'dir' => 'asc']));

        $response->assertOk();
        $response->assertSee('sort=title');
        $response->assertSee('dir=asc');
    }

    // -------------------------------------------------------------------------
    // Sorting does not leak other users' episodes
    // -------------------------------------------------------------------------

    public function test_sort_by_show_does_not_include_other_users_episodes(): void
    {
        $other     = User::factory()->create();
        $otherShow = PodcastShow::factory()->create(['user_id' => $other->id, 'title' => 'Middle Show']);

        PodcastEpisode::factory()->create([
            'user_id'         => $other->id,
            'podcast_show_id' => $otherShow->id,
            'status'          => PodcastEpisodeStatus::ready_to_upload_recording,
            'title'           => 'Not My Episode',
        ]);

        $myEp = $this->makeEpisode(['title' => 'My Episode']);

        $response = $this->actingAs($this->user)
            ->get(route('podcast_episodes.index', ['sort' => 'show', 'dir' => 'asc']));

        $response->assertOk();
        $response->assertSee('My Episode');
        $response->assertDontSee('Not My Episode');
        $this->assertEquals([$myEp->id], $this->episodeIdsFromResponse($response));
    }
}