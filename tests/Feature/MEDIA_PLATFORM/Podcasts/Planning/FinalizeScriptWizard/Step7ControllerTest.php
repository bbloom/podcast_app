<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step7ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user, ?string $outroTemplate = 'Goodbye {{title}}'): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create([
            'user_id'        => $user->id,
            'outro_template' => $outroTemplate,
        ]);
        $episode = PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_renders_when_outro_template_exists(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, 'Goodbye {{title}}');

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step7'))
            ->assertOk();
    }

    public function test_show_renders_when_no_outro_template(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, null);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step7'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step7'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.finalize.step7'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store — action = 'save'
    // -------------------------------------------------------------------------

    public function test_store_save_updates_show_outro_template_and_redirects_to_step8(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, 'Old outro.');
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
                '_action'        => 'save',
                'outro_template' => 'Updated outro {{title}}.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step8'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'outro_template' => 'Updated outro {{title}}.',
        ]);
    }

    public function test_store_save_with_empty_template_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, 'Old outro.');

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
                '_action'        => 'save',
                'outro_template' => '',
            ])
            ->assertSessionHasErrors('outro_template');
    }

    public function test_store_save_creates_outro_template_when_none_existed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, null);
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
                '_action'        => 'save',
                'outro_template' => 'Brand new outro {{episode_number}}.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step8'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'outro_template' => 'Brand new outro {{episode_number}}.',
        ]);
    }

    // -------------------------------------------------------------------------
    // store — action = 'continue' (no changes)
    // -------------------------------------------------------------------------

    public function test_store_continue_does_not_modify_show_and_redirects_to_step8(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, 'Existing outro.');
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
                '_action'        => 'continue',
                'outro_template' => 'Existing outro.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step8'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'outro_template' => 'Existing outro.',
        ]);
    }

    // -------------------------------------------------------------------------
    // store — session/auth
    // -------------------------------------------------------------------------

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
                '_action'        => 'continue',
                'outro_template' => 'x',
            ])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.finalize.step7.store'), [
            '_action'        => 'continue',
            'outro_template' => 'x',
        ])->assertRedirect(route('login'));
    }
}