<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class EditThemeFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user, array $overrides = []): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisodePlanning::factory()->create(array_merge([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_for_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.theme.show', $episode))
            ->assertOk();
    }

    public function test_show_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.theme.show', $ep))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $ep = $this->makeEpisode(User::factory()->create());
        $this->get(route('podcast_episodes_planning.theme.show', $ep))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // save (Alpine fetch — returns JSON)
    // -------------------------------------------------------------------------

    public function test_save_updates_theme_and_returns_json_success(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->patch(route('podcast_episodes_planning.theme.save', $episode), [
                'theme' => 'New theme content.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'theme' => 'New theme content.',
        ]);
    }

    public function test_save_returns_403_json_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);

        $this->actingAs($user)
            ->patch(route('podcast_episodes_planning.theme.save', $ep), ['theme' => 'x'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_save_redirects_unauthenticated_users(): void
    {
        $ep = $this->makeEpisode(User::factory()->create());
        $this->patch(route('podcast_episodes_planning.theme.save', $ep), ['theme' => 'x'])
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // saveAndExit (form submit — returns redirect)
    // -------------------------------------------------------------------------

    public function test_save_and_exit_updates_theme_and_redirects_to_show(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->patch(route('podcast_episodes_planning.theme.save_exit', $episode), [
                'theme' => 'Theme from exit.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes_planning', [
            'id'    => $episode->id,
            'theme' => 'Theme from exit.',
        ]);
    }

    public function test_save_and_exit_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);

        $this->actingAs($user)
            ->patch(route('podcast_episodes_planning.theme.save_exit', $ep), ['theme' => 'x'])
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_save_and_exit_redirects_unauthenticated_users(): void
    {
        $ep = $this->makeEpisode(User::factory()->create());
        $this->patch(route('podcast_episodes_planning.theme.save_exit', $ep), ['theme' => 'x'])
            ->assertRedirect(route('login'));
    }
}