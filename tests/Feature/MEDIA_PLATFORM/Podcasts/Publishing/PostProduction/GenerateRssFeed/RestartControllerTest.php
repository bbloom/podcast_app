<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class RestartControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function episodeForUser(
        User $user,
        PodcastEpisodeStatus $status = PodcastEpisodeStatus::rss_validation_failed
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

        $this->get(route('post_production.generate_rss_feed.restart', $episode))
            ->assertRedirect(route('login'));
    }

    // ── Ownership ────────────────────────────────────────────────────────────

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.generate_rss_feed.restart', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ── Status guard ──────────────────────────────────────────────────────────

    public function test_wrong_status_is_redirected_with_error(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_wrong_status_does_not_change_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }

    // ── Status reset: rss_validation_failed ───────────────────────────────────

    public function test_rss_validation_failed_resets_to_ready_to_generate_rss_feed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::rss_validation_failed);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }

    public function test_rss_validation_failed_redirects_to_step1(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::rss_validation_failed);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode))
            ->assertSessionHas('success');
    }

    // ── Status reset: ready_to_upload_rss_feed (session expired recovery) ─────

    public function test_ready_to_upload_rss_feed_resets_to_ready_to_generate_rss_feed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_upload_rss_feed);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }

    public function test_ready_to_upload_rss_feed_redirects_to_step1(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_upload_rss_feed);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.restart', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step1', $episode));
    }

    // ── Session cleanup ───────────────────────────────────────────────────────

    public function test_wizard_session_keys_are_cleared(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->withSession([
                'wizard.generate_rss_feed.podcast_episode_id' => $episode->id,
                'wizard.generate_rss_feed.rss_filename'       => 'my-feed.xml',
                'wizard.generate_rss_feed.live_s3_url'        => 'https://example.com/feed.xml',
                'wizard.generate_rss_feed.staging_url'        => 'https://staging.example.com/feed.xml',
            ])
            ->get(route('post_production.generate_rss_feed.restart', $episode));

        $this->assertNull(session('wizard.generate_rss_feed.podcast_episode_id'));
        $this->assertNull(session('wizard.generate_rss_feed.rss_filename'));
        $this->assertNull(session('wizard.generate_rss_feed.live_s3_url'));
        $this->assertNull(session('wizard.generate_rss_feed.staging_url'));
    }
}