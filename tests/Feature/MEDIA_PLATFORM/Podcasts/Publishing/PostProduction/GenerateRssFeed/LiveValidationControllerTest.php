<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class LiveValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function episodeForUser(
        User $user,
        PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_upload_rss_feed
    ): PodcastEpisode {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // =========================================================================
    // show()
    // =========================================================================

    public function test_show_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = $this->episodeForUser(User::factory()->create());

        $this->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_show_wrong_status_is_redirected(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_show_owner_with_correct_status_sees_the_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->withSession(['wizard.generate_rss_feed.live_s3_url' => 'https://example.com/feed.xml'])
            ->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertOk()
            ->assertViewHas('episode');
    }

    public function test_show_passes_live_s3_url_from_session_to_view(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);
        $url     = 'https://my-bucket.s3.eu-west-1.amazonaws.com/rss/my-feed.xml';

        $this->actingAs($user)
            ->withSession(['wizard.generate_rss_feed.live_s3_url' => $url])
            ->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertOk()
            ->assertViewHas('liveS3Url', $url)
            ->assertViewHas('sessionExpired', false);
    }

    public function test_show_sets_session_expired_true_when_no_url_in_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.live_validation', $episode))
            ->assertOk()
            ->assertViewHas('sessionExpired', true)
            ->assertViewHas('liveS3Url', null);
    }

    // =========================================================================
    // promoteToR2() — guard paths only.
    // The actual R2 upload requires live AWS credentials and is an integration
    // test. These tests cover all paths that do not reach the S3Client call.
    // =========================================================================

    public function test_promote_to_r2_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = $this->episodeForUser(User::factory()->create());

        $this->post(route('post_production.generate_rss_feed.live_validation.promote', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_promote_to_r2_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->post(route('post_production.generate_rss_feed.live_validation.promote', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_wrong_status_is_redirected(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.promote', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_no_session_filename_redirects_to_restart(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        // No session — filename is absent.
        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.promote', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.restart', $episode))
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_no_local_file_redirects_to_restart(): void
    {
        $user     = User::factory()->create();
        $episode  = $this->episodeForUser($user);
        $filename = 'non-existent-' . uniqid() . '.xml';

        $this->actingAs($user)
            ->withSession(['wizard.generate_rss_feed.rss_filename' => $filename])
            ->post(route('post_production.generate_rss_feed.live_validation.promote', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.restart', $episode))
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_wrong_status_does_not_change_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.promote', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }

    // =========================================================================
    // fail()
    // =========================================================================

    public function test_fail_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = $this->episodeForUser(User::factory()->create());

        $this->post(route('post_production.generate_rss_feed.live_validation.fail', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_fail_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_fail_wrong_status_is_redirected(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_fail_sets_status_to_rss_validation_failed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::rss_validation_failed->value,
        ]);
    }

    public function test_fail_redirects_to_episode_show_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_fail_clears_wizard_session_keys(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->withSession([
                'wizard.generate_rss_feed.rss_filename'  => 'my-feed.xml',
                'wizard.generate_rss_feed.live_s3_url'   => 'https://example.com/feed.xml',
                'wizard.generate_rss_feed.podcast_episode_id' => $episode->id,
            ])
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode));

        $this->assertNull(session('wizard.generate_rss_feed.rss_filename'));
        $this->assertNull(session('wizard.generate_rss_feed.live_s3_url'));
        $this->assertNull(session('wizard.generate_rss_feed.podcast_episode_id'));
    }

    public function test_fail_wrong_status_does_not_change_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.live_validation.fail', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }
}