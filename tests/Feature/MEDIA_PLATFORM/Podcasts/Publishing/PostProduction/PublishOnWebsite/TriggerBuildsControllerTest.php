<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use Tests\TestCase;

class TriggerBuildsControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function userWithShowAndHook(): array
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        $hook = DeployHook::factory()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
        ]);

        return [$user, $show, $hook];
    }

    private function episodeForShow(PodcastShow $show, PodcastEpisodeStatus $status): PodcastEpisode
    {
        return PodcastEpisode::factory()->create([
            'user_id'         => $show->user_id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    private function mockTriggerService(): void
    {
        // Bind a stub that returns a successful result without real HTTP calls.
        $this->instance(
            DeployHookTriggerService::class,
            new class extends DeployHookTriggerService {
                public function trigger(DeployHook $hook): \MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerResult
                {
                    return \MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerResult::success(
                        hook:       $hook,
                        httpStatus: 200,
                        buildId:    'fake-build-id',
                    );
                }
            }
        );
    }

    // ── Pipeline flow (session episode present) ───────────────────────────────

    public function test_pipeline_flow_advances_episode_to_build_triggered(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();
        $episode = $this->episodeForShow($show, PodcastEpisodeStatus::website_published);

        $this->actingAs($user)
            ->withSession(['build_confirmation.pending_episode_id' => $episode->id])
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ]);

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::build_triggered->value,
        ]);
    }

    public function test_pipeline_flow_redirects_to_build_confirmation(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();
        $episode = $this->episodeForShow($show, PodcastEpisodeStatus::website_published);

        $this->actingAs($user)
            ->withSession(['build_confirmation.pending_episode_id' => $episode->id])
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ])
            ->assertRedirect(route('post_production.build_confirmation.show', $episode));
    }

    public function test_pipeline_flow_clears_session_key_after_use(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();
        $episode = $this->episodeForShow($show, PodcastEpisodeStatus::website_published);

        $this->actingAs($user)
            ->withSession(['build_confirmation.pending_episode_id' => $episode->id])
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ]);

        $this->assertNull(session('build_confirmation.pending_episode_id'));
    }

    public function test_pipeline_flow_does_not_advance_episode_with_wrong_status(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();

        // Episode is published (not website_published) — should be ignored.
        $episode = $this->episodeForShow($show, PodcastEpisodeStatus::published);

        $this->actingAs($user)
            ->withSession(['build_confirmation.pending_episode_id' => $episode->id])
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ])
            ->assertRedirect(route('post_production.trigger_builds.results', $show));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::published->value,
        ]);
    }

    // ── Manual flow (no session episode) ─────────────────────────────────────

    public function test_manual_flow_redirects_to_results_page(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();

        $this->actingAs($user)
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ])
            ->assertRedirect(route('post_production.trigger_builds.results', $show));
    }

    public function test_manual_flow_stores_results_in_session(): void
    {
        $this->mockTriggerService();
        [$user, $show, $hook] = $this->userWithShowAndHook();

        $this->actingAs($user)
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [$hook->id],
            ]);

        $this->assertNotEmpty(session('trigger_builds.results'));
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_no_hooks_selected_redirects_with_error(): void
    {
        [$user, $show] = $this->userWithShowAndHook();

        $this->actingAs($user)
            ->post(route('post_production.trigger_builds.trigger', $show), [
                'hook_ids' => [],
            ])
            ->assertRedirect(route('post_production.trigger_builds.select', $show))
            ->assertSessionHas('error');
    }
}