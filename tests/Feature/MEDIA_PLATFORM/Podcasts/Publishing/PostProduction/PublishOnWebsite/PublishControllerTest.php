<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PublishControllerTest extends TestCase
{
    use RefreshDatabase;

    private function episodeForUser(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_publish_website): PodcastEpisode
    {
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

        $this->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('login'));
    }

    // ── Ownership ────────────────────────────────────────────────────────────

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ── Status advancement ────────────────────────────────────────────────────

    public function test_sets_website_enabled_to_true(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'              => $episode->id,
            'website_enabled' => true,
        ]);
    }

    public function test_advances_status_to_website_published(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::website_published->value,
        ]);
    }

    public function test_also_accepts_legacy_ready_to_publish_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect();

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::website_published->value,
        ]);
    }

    // ── Session ───────────────────────────────────────────────────────────────

    public function test_stores_episode_id_in_session_for_trigger_builds(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertEquals(
            $episode->id,
            session('build_confirmation.pending_episode_id'),
        );
    }

    // ── Redirect ─────────────────────────────────────────────────────────────

    public function test_redirects_to_trigger_builds_select(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.trigger_builds.select', $episode->show))
            ->assertSessionHas('success');
    }

    // ── Status guard ──────────────────────────────────────────────────────────

    public function test_wrong_status_redirects_with_error(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_wrong_status_does_not_change_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'              => $episode->id,
            'status'          => PodcastEpisodeStatus::ready_for_auphonic->value,
            'website_enabled' => false,
        ]);
    }
}