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
            'script'          => 'Original script.',
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    public function test_show_renders_when_intro_template_exists(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertOk();
    }

    public function test_show_auto_skips_to_step6_when_no_intro_template(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, null);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step5'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_prepend_prepends_intro_to_script(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action'    => 'prepend',
                'intro_text' => 'INTRO TEXT',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));

        $episode->refresh();
        $this->assertStringStartsWith('INTRO TEXT', $episode->script);
        $this->assertStringContainsString('Original script.', $episode->script);
    }

    public function test_store_skip_does_not_modify_script(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), [
                '_action' => 'skip',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step6'));

        $episode->refresh();
        $this->assertEquals('Original script.', $episode->script);
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step5.store'), ['_action' => 'skip'])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }
}