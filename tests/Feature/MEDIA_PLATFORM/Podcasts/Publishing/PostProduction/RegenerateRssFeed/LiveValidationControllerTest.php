<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\RegenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class LiveValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function showForUser(User $user): PodcastShow
    {
        return PodcastShow::factory()->create(['user_id' => $user->id]);
    }

    // =========================================================================
    // show()
    // =========================================================================

    public function test_show_unauthenticated_user_is_redirected_to_login(): void
    {
        $show = $this->showForUser(User::factory()->create());

        $this->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertRedirect(route('login'));
    }

    public function test_show_non_owner_is_redirected_with_error(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $show  = $this->showForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_show_owner_sees_the_page(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);

        $this->actingAs($user)
            ->withSession([
                'regenerate_rss_feed.live_s3_url' => 'https://example.com/feed.xml',
                'regenerate_rss_feed.show_id'     => $show->id,
            ])
            ->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertOk()
            ->assertViewHas('show');
    }

    public function test_show_passes_live_s3_url_from_session_to_view(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);
        $url  = 'https://my-bucket.s3.eu-west-1.amazonaws.com/rss/my-show.xml';

        $this->actingAs($user)
            ->withSession([
                'regenerate_rss_feed.live_s3_url' => $url,
                'regenerate_rss_feed.show_id'     => $show->id,
            ])
            ->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertOk()
            ->assertViewHas('liveS3Url', $url)
            ->assertViewHas('sessionExpired', false);
    }

    public function test_show_sets_session_expired_true_when_no_url_in_session(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertOk()
            ->assertViewHas('sessionExpired', true)
            ->assertViewHas('liveS3Url', null);
    }

    public function test_show_mismatched_show_id_in_session_redirects_with_error(): void
    {
        $user      = User::factory()->create();
        $show      = $this->showForUser($user);
        $otherShow = $this->showForUser($user);

        $this->actingAs($user)
            ->withSession([
                'regenerate_rss_feed.live_s3_url' => 'https://example.com/feed.xml',
                'regenerate_rss_feed.show_id'     => $otherShow->id, // different show
            ])
            ->get(route('post_production.regenerate_rss_feed.live_validation', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertSessionHas('error');
    }

    // =========================================================================
    // promoteToR2() — guard paths only.
    // The actual R2 upload requires live AWS credentials and is an integration
    // test. These tests cover all paths that do not reach the S3Client call.
    // =========================================================================

    public function test_promote_to_r2_unauthenticated_user_is_redirected_to_login(): void
    {
        $show = $this->showForUser(User::factory()->create());

        $this->post(route('post_production.regenerate_rss_feed.live_validation.promote', $show))
            ->assertRedirect(route('login'));
    }

    public function test_promote_to_r2_non_owner_is_redirected_with_error(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $show  = $this->showForUser($owner);

        $this->actingAs($other)
            ->post(route('post_production.regenerate_rss_feed.live_validation.promote', $show))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_no_session_filename_redirects_to_stage(): void
    {
        $user = User::factory()->create();
        $show = $this->showForUser($user);

        // No session — filename absent.
        $this->actingAs($user)
            ->post(route('post_production.regenerate_rss_feed.live_validation.promote', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_mismatched_show_id_redirects_to_stage(): void
    {
        $user      = User::factory()->create();
        $show      = $this->showForUser($user);
        $otherShow = $this->showForUser($user);

        $this->actingAs($user)
            ->withSession([
                'regenerate_rss_feed.rss_filename' => 'feed.xml',
                'regenerate_rss_feed.show_id'      => $otherShow->id,
            ])
            ->post(route('post_production.regenerate_rss_feed.live_validation.promote', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertSessionHas('error');
    }

    public function test_promote_to_r2_no_local_file_redirects_to_stage(): void
    {
        $user     = User::factory()->create();
        $show     = $this->showForUser($user);
        $filename = 'non-existent-' . uniqid() . '.xml';

        $this->actingAs($user)
            ->withSession([
                'regenerate_rss_feed.rss_filename' => $filename,
                'regenerate_rss_feed.show_id'      => $show->id,
            ])
            ->post(route('post_production.regenerate_rss_feed.live_validation.promote', $show))
            ->assertRedirect(route('post_production.regenerate_rss_feed.stage', $show))
            ->assertSessionHas('error');
    }
}