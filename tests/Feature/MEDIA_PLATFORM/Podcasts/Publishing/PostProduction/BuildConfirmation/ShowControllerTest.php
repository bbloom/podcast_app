<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\BuildConfirmation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function episodeForUser(
        User $user,
        PodcastEpisodeStatus $status = PodcastEpisodeStatus::build_triggered,
    ): PodcastEpisode {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = $this->episodeForUser(User::factory()->create());

        $this->get(route('post_production.build_confirmation.show', $episode))
            ->assertRedirect(route('login'));
    }

    // ── Ownership ────────────────────────────────────────────────────────────

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ── Status guard ──────────────────────────────────────────────────────────

    public function test_owner_with_build_triggered_status_sees_the_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('episode');
    }

    public function test_wrong_status_redirects_to_correct_pipeline_step(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertRedirect(route(
                PodcastEpisodeStatus::ready_for_auphonic->postProductionShowRoute(),
                $episode,
            ))
            ->assertSessionHas('error');
    }

    public function test_published_episode_is_redirected_away(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::published);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ── Cloudflare hook resolution ────────────────────────────────────────────

    public function test_cloudflare_hook_is_passed_to_view_when_present(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        DeployHook::factory()->succeeded('build-abc-123')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', fn ($hook) => $hook !== null);
    }

    public function test_cloudflare_hook_is_null_when_no_hook_exists_for_the_show(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        // No deploy hooks seeded for this show.

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', null);
    }

    public function test_netlify_hook_is_not_resolved_as_cloudflare_hook(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        DeployHook::factory()->netlify()->succeeded('netlify-id')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', null);
    }

    public function test_cloudflare_hook_without_build_id_is_not_resolved(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        // Hook exists but has never been triggered — no build ID.
        DeployHook::factory()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
            'last_build_id'    => null,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', null);
    }

    public function test_disabled_cloudflare_hook_is_not_resolved(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        DeployHook::factory()->disabled()->succeeded('build-id')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', null);
    }

    public function test_most_recently_triggered_hook_is_resolved_when_multiple_exist(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $older = DeployHook::factory()->succeeded('old-build-id')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
            'last_triggered_at' => now()->subHours(5),
        ]);

        $newer = DeployHook::factory()->succeeded('new-build-id')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $episode->podcast_show_id,
            'last_triggered_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.show', $episode))
            ->assertOk()
            ->assertViewHas('cloudflareHook', fn ($hook) => $hook->id === $newer->id);
    }
}