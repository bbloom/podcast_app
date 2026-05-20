<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning\FinalizeScriptWizard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step6ControllerTest extends TestCase
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
            'script'          => 'Body script.',
        ]);
        session(['wizard.finalize_script.episode_id' => $episode->id]);
        return $episode;
    }

    public function test_show_renders_when_outro_template_exists(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step6'))
            ->assertOk();
    }

    public function test_show_auto_skips_to_step7_when_no_outro_template(): void
    {
        $user = User::factory()->create();
        $this->makeEpisodeWithSession($user, null);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.wizard.finalize.step6'))
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step7'));
    }

    public function test_show_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('podcast_episodes_planning.wizard.finalize.step6'))
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }

    public function test_store_append_appends_outro_to_script(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step6.store'), [
                '_action'    => 'append',
                'outro_text' => 'OUTRO TEXT',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step7'));

        $episode->refresh();
        $this->assertStringEndsWith('OUTRO TEXT', $episode->script);
        $this->assertStringContainsString('Body script.', $episode->script);
    }

    public function test_store_skip_does_not_modify_script(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeWithSession($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.wizard.finalize.step6.store'), [
                '_action' => 'skip',
            ])
            ->assertRedirect(route('podcast_episodes_planning.wizard.finalize.step7'));

        $episode->refresh();
        $this->assertEquals('Body script.', $episode->script);
    }

    public function test_store_redirects_without_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('podcast_episodes_planning.wizard.finalize.step6.store'), ['_action' => 'skip'])
            ->assertRedirect(route('podcast_episodes_planning.index'));
    }
}