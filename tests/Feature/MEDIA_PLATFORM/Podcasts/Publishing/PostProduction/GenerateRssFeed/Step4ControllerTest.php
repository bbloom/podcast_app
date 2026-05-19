<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step4ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEpisode(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_generate_rss_feed): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    private function withFullSession(PodcastEpisode $episode): array
    {
        return [
            'wizard.generate_rss_feed.podcast_episode_id' => $episode->id,
            'wizard.generate_rss_feed.staging_url'        => 'https://staging.example.com/rss/feed.xml',
            'wizard.generate_rss_feed.rss_filename'       => 'rss_test_show.xml',
            'wizard.generate_rss_feed.rss_s3_key'         => 'rss/rss_test_show.xml',
        ];
    }

    // -------------------------------------------------------------------------
    // show (GET) — access guards
    // -------------------------------------------------------------------------

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('post_production.generate_rss_feed.step4', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->get(route('post_production.generate_rss_feed.step4', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->get(route('post_production.generate_rss_feed.step4', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_to_step3_when_staging_url_missing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->withSession(['wizard.generate_rss_feed.podcast_episode_id' => $episode->id])
            ->get(route('post_production.generate_rss_feed.step4', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step3', $episode));
    }

    // -------------------------------------------------------------------------
    // show (GET) — happy path
    // -------------------------------------------------------------------------

    public function test_show_renders_with_staging_url(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->get(route('post_production.generate_rss_feed.step4', $episode))
            ->assertOk()
            ->assertSee('https://staging.example.com/rss/feed.xml')
            ->assertSee('Cast Feed Validator')
            ->assertSee('Podbase');
    }

    // -------------------------------------------------------------------------
    // failed (POST)
    // -------------------------------------------------------------------------

    public function test_failed_redirects_to_episode_show_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step4.failed', $episode))
            ->assertRedirect(route('podcast_episodes.show', $episode))
            ->assertSessionHas('error');
    }

    public function test_failed_clears_wizard_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step4.failed', $episode));

        $this->assertNull(session('wizard.generate_rss_feed.podcast_episode_id'));
        $this->assertNull(session('wizard.generate_rss_feed.staging_url'));
    }

    public function test_failed_does_not_change_episode_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step4.failed', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }

    public function test_failed_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->post(route('post_production.generate_rss_feed.step4.failed', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_failed_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->withSession($this->withFullSession($episode))
            ->post(route('post_production.generate_rss_feed.step4.failed', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }
}