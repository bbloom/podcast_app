<?php

namespace Tests\Feature\MEDIA_PLATFORM\Videos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Videos\Enums\VideoStatus;
use MediaPlatform\Videos\Models\Video;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a video belonging to the given user.
     */
    private function videoForUser(User $user, array $overrides = []): Video
    {
        return Video::factory()->forUser($user)->create($overrides);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_only_the_authenticated_users_videos(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $this->videoForUser($user, ['title' => 'My Video']);
        $this->videoForUser($other, ['title' => 'Their Video']);

        $this->actingAs($user)
            ->get(route('videos.index'))
            ->assertOk()
            ->assertSee('My Video')
            ->assertDontSee('Their Video');
    }

    public function test_index_is_sorted_by_id_descending(): void
    {
        $user = User::factory()->create();

        $first  = $this->videoForUser($user, ['title' => 'First Video']);
        $second = $this->videoForUser($user, ['title' => 'Second Video']);

        $response = $this->actingAs($user)
            ->get(route('videos.index'))
            ->assertOk();

        $response->assertSeeInOrder(['Second Video', 'First Video']);
    }

    public function test_index_paginates_results(): void
    {
        $user = User::factory()->create();

        Video::factory()->forUser($user)->count(30)->create();

        $this->actingAs($user)
            ->get(route('videos.index'))
            ->assertOk();
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('videos.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_displays_the_users_own_video(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user, ['title' => 'My Video']);

        $this->actingAs($user)
            ->get(route('videos.show', $video))
            ->assertOk()
            ->assertSee('My Video');
    }

    public function test_show_redirects_for_another_users_video(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $video = $this->videoForUser($other);

        $this->actingAs($user)
            ->get(route('videos.show', $video))
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('error');
    }

    public function test_show_returns_404_for_non_existent_video(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('videos.show', 99999))
            ->assertNotFound();
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $video = Video::factory()->create();

        $this->get(route('videos.show', $video))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_shows_form_to_the_videos_owner(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->get(route('videos.edit', $video))
            ->assertOk();
    }

    public function test_edit_redirects_for_another_users_video(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $video = $this->videoForUser($other);

        $this->actingAs($user)
            ->get(route('videos.edit', $video))
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_redirects_unauthenticated_users(): void
    {
        $video = Video::factory()->create();

        $this->get(route('videos.edit', $video))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_persists_changes(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->put(route('videos.update', $video), [
                'title'               => 'Updated Title',
                'slug'                => 'updated-title',
                'description'         => 'Updated description.',
                'scheduled_date'      => '2026-07-01',
                'status'              => VideoStatus::published_to_youtube->value,
                'youtube_title'       => 'YT Updated Title',
                'youtube_description' => 'YT Updated Desc',
                'youtube_chapters'    => '0:00 Intro',
                'youtube_url'         => 'https://www.youtube.com/watch?v=abc123',
            ])
            ->assertRedirect(route('videos.show', $video))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('videos', [
            'id'    => $video->id,
            'title' => 'Updated Title',
            'slug'  => 'updated-title',
        ]);
    }

    public function test_update_redirects_for_another_users_video(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $video = $this->videoForUser($other);

        $this->actingAs($user)
            ->put(route('videos.update', $video), [
                'title'          => 'Hacked',
                'slug'           => 'hacked',
                'description'    => 'Hacked.',
                'scheduled_date' => '2026-07-01',
                'status'         => VideoStatus::not_published_to_youtube->value,
            ])
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('error');
    }

    public function test_update_validates_required_fields(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->put(route('videos.update', $video), [])
            ->assertSessionHasErrors(['title', 'slug', 'description', 'status']);
    }

    public function test_update_validates_slug_uniqueness(): void
    {
        $user   = User::factory()->create();
        $video1 = $this->videoForUser($user, ['slug' => 'existing-slug']);
        $video2 = $this->videoForUser($user, ['slug' => 'other-slug']);

        $this->actingAs($user)
            ->put(route('videos.update', $video2), [
                'title'       => 'Test',
                'slug'        => 'existing-slug',
                'description' => 'Test.',
                'status'      => VideoStatus::not_published_to_youtube->value,
            ])
            ->assertSessionHasErrors(['slug']);
    }

    public function test_update_allows_keeping_own_slug(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user, ['slug' => 'my-slug']);

        $this->actingAs($user)
            ->put(route('videos.update', $video), [
                'title'          => 'Test',
                'slug'           => 'my-slug',
                'description'    => 'Test.',
                'scheduled_date' => '2026-07-01',
                'status'         => VideoStatus::not_published_to_youtube->value,
            ])
            ->assertRedirect(route('videos.show', $video))
            ->assertSessionHas('success');
    }

    public function test_update_validates_youtube_url_format(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->put(route('videos.update', $video), [
                'title'       => 'Test',
                'slug'        => 'test',
                'description' => 'Test.',
                'status'      => VideoStatus::not_published_to_youtube->value,
                'youtube_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors(['youtube_url']);
    }

    public function test_update_redirects_unauthenticated_users(): void
    {
        $video = Video::factory()->create();

        $this->put(route('videos.update', $video), ['title' => 'Hacked'])
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    public function test_delete_confirm_shows_page_to_owner(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->get(route('videos.delete.confirm', $video))
            ->assertOk()
            ->assertSee('Delete Video');
    }

    public function test_delete_confirm_redirects_for_another_users_video(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $video = $this->videoForUser($other);

        $this->actingAs($user)
            ->get(route('videos.delete.confirm', $video))
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('error');
    }

    public function test_delete_confirm_redirects_unauthenticated_users(): void
    {
        $video = Video::factory()->create();

        $this->get(route('videos.delete.confirm', $video))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_video(): void
    {
        $user  = User::factory()->create();
        $video = $this->videoForUser($user);

        $this->actingAs($user)
            ->delete(route('videos.destroy', $video))
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    public function test_destroy_redirects_for_another_users_video(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $video = $this->videoForUser($other);

        $this->actingAs($user)
            ->delete(route('videos.destroy', $video))
            ->assertRedirect(route('videos.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    public function test_destroy_redirects_unauthenticated_users(): void
    {
        $video = Video::factory()->create();

        $this->delete(route('videos.destroy', $video))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    public function test_destroy_returns_404_for_non_existent_video(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('videos.destroy', 99999))
            ->assertNotFound();
    }
}