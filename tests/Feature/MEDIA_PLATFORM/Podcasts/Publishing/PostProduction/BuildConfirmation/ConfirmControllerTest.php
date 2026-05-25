<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\BuildConfirmation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class ConfirmControllerTest extends TestCase
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

        $this->get(route('post_production.build_confirmation.confirm', $episode))
            ->assertRedirect(route('login'));
    }

    // ── Ownership ────────────────────────────────────────────────────────────

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.build_confirmation.confirm', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_non_owner_does_not_change_episode_status(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.build_confirmation.confirm', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::build_triggered->value,
        ]);
    }

    // ── Status advancement ────────────────────────────────────────────────────

    public function test_advances_episode_from_build_triggered_to_ready_to_generate_rss_feed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }

    public function test_redirects_to_generate_rss_feed_step1_after_confirming(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode));
    }

    // ── Status guard ──────────────────────────────────────────────────────────

    public function test_wrong_status_redirects_with_error(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_wrong_status_does_not_change_episode_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }

    // ── Idempotency ───────────────────────────────────────────────────────────

    public function test_already_at_ready_to_generate_rss_feed_redirects_to_step1(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode));
    }

    public function test_idempotent_confirm_does_not_modify_already_advanced_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->get(route('post_production.build_confirmation.confirm', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }
}