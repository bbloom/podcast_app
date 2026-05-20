<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PodcastEpisodePlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a planning episode belonging to the given user.
     */
    private function makeEpisode(User $user, array $overrides = []): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisodePlanning::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ], $overrides));
    }

    /**
     * Valid update payload.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'          => 'My Episode Title',
            'episode_number' => 10,
            'scheduled_date' => '2026-09-01',
            'status'         => PodcastEpisodePlanningStatus::writing_script->value,
            'notes'          => 'Some notes.',
            'theme'          => 'A theme.',
            'script'         => 'A script.',
            'website_content' => '<p>Content.</p>',
            'website_excerpt' => 'Excerpt.',
        ], $overrides);
    }

    // =========================================================================
    // index
    // =========================================================================

    public function test_index_shows_episodes_to_authenticated_user(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, ['title' => 'My Planning Episode']);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.index'))
            ->assertOk()
            ->assertSee('My Planning Episode');
    }

    public function test_index_only_shows_own_episodes(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $this->makeEpisode($user,  ['title' => 'Mine']);
        $this->makeEpisode($other, ['title' => 'Theirs']);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_defaults_to_id_descending(): void
    {
        $user = User::factory()->create();
        $this->makeEpisode($user, ['title' => 'First']);
        $this->makeEpisode($user, ['title' => 'Second']);

        $response = $this->actingAs($user)
            ->get(route('podcast_episodes_planning.index'))
            ->assertOk();

        $this->assertLessThan(
            strpos($response->getContent(), 'First'),
            strpos($response->getContent(), 'Second')
        );
    }

    // =========================================================================
    // show
    // =========================================================================

    public function test_show_displays_episode_to_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, ['title' => 'My Episode']);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.show', $episode))
            ->assertOk()
            ->assertSee('My Episode');
    }

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.show', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_episodes_planning.show', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.show', 99999))
            ->assertNotFound();
    }

    // =========================================================================
    // edit
    // =========================================================================

    public function test_edit_shows_form_to_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.edit', $episode))
            ->assertOk();
    }

    public function test_edit_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.edit', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_episodes_planning.edit', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_edit_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.edit', 99999))
            ->assertNotFound();
    }

    // =========================================================================
    // update
    // =========================================================================

    public function test_update_saves_all_fields(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->put(route('podcast_episodes_planning.update', $episode), $this->validPayload())
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'             => $episode->id,
            'title'          => 'My Episode Title',
            'episode_number' => 10,
            'status'         => 'writing-script',
            'notes'          => 'Some notes.',
            'theme'          => 'A theme.',
        ]);
    }

    public function test_update_validates_required_title(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->put(route('podcast_episodes_planning.update', $episode), $this->validPayload(['title' => '']))
            ->assertSessionHasErrors(['title']);
    }

    public function test_update_rejects_wizard_managed_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        // ready_to_record is set by the Finalize Script Wizard, not manually.
        $this->actingAs($user)
            ->put(route('podcast_episodes_planning.update', $episode), $this->validPayload([
                'status' => PodcastEpisodePlanningStatus::ready_to_record->value,
            ]))
            ->assertSessionHasErrors(['status']);
    }

    public function test_update_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->put(route('podcast_episodes_planning.update', $episode), $this->validPayload())
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_update_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->put(route('podcast_episodes_planning.update', $episode), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_update_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('podcast_episodes_planning.update', 99999), $this->validPayload())
            ->assertNotFound();
    }

    // =========================================================================
    // deleteConfirm
    // =========================================================================

    public function test_delete_confirm_shows_page_to_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.delete.confirm', $episode))
            ->assertOk();
    }

    public function test_delete_confirm_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.delete.confirm', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_delete_confirm_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('podcast_episodes_planning.delete.confirm', $episode))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // destroy
    // =========================================================================

    public function test_destroy_hard_deletes_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.destroy', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('podcast_episodes_planning', ['id' => $episode->id]);
    }

    public function test_destroy_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.destroy', $episode))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_destroy_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->delete(route('podcast_episodes_planning.destroy', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_destroy_returns_404_for_non_existent_episode(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('podcast_episodes_planning.destroy', 99999))
            ->assertNotFound();
    }
}