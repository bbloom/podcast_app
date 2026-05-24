<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step5ControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisodeWithSession(User $user, ?string $introTemplate = 'Hello {{title}}'): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create([
            'user_id'        => $user->id,
            'intro_template' => $introTemplate,
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

    public function test_show_renders_when_intro_template_exists(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, 'Hello {{title}}');

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertOk();
    }

    public function test_show_renders_when_no_intro_template(): void
    {
        // No auto-skip — the view always renders regardless of whether a template exists.
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, null);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertOk();
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $this->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // store — action = 'save'
    // -------------------------------------------------------------------------

    public function test_store_save_updates_show_intro_template_and_redirects_to_step6(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, 'Old intro.');
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'        => 'save',
                'intro_template' => 'Updated intro {{title}}.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'intro_template' => 'Updated intro {{title}}.',
        ]);
    }

    public function test_store_save_with_empty_template_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, 'Old intro.');

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'        => 'save',
                'intro_template' => '',
            ])
            ->assertSessionHasErrors('intro_template');
    }

    public function test_store_save_creates_intro_template_when_none_existed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, null);
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'        => 'save',
                'intro_template' => 'Brand new intro {{episode_number}}.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'intro_template' => 'Brand new intro {{episode_number}}.',
        ]);
    }

    // -------------------------------------------------------------------------
    // store — action = 'continue' (no changes)
    // -------------------------------------------------------------------------

    public function test_store_continue_does_not_modify_show_and_redirects_to_step6(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user, 'Existing intro.');
        $show    = $episode->show;

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'        => 'continue',
                'intro_template' => 'Existing intro.',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));

        $this->assertDatabaseHas('podcast_shows', [
            'id'             => $show->id,
            'intro_template' => 'Existing intro.',
        ]);
    }

    // -------------------------------------------------------------------------
    // store — session/auth
    // -------------------------------------------------------------------------

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'        => 'continue',
                'intro_template' => 'x',
            ])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_redirects_unauthenticated_users(): void
    {
        $this->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
            '_action'        => 'continue',
            'intro_template' => 'x',
        ])->assertRedirect(route('login'));
    }
}