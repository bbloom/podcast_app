<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class Step1ControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a show and episode belonging to the given user.
     */
    private function episodeForUser(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_generate_rss_feed): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // -------------------------------------------------------------------------
    // show (GET)
    // -------------------------------------------------------------------------

    public function test_show_renders_for_episode_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step1', $episode))
            ->assertOk();
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->get(route('post_production.generate_rss_feed.step1', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step1', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_redirects_when_episode_has_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step1', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_show_displays_enclosure_length_and_duration(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        $episode = PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'itunes_enclosure_length'  => '45678901',
            'itunes_duration'          => '01:15:32',
        ]);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.step1', $episode))
            ->assertOk()
            ->assertSee('45,678,901')
            ->assertSee('01:15:32');
    }

    // -------------------------------------------------------------------------
    // store (POST)
    // -------------------------------------------------------------------------

    public function test_store_redirects_to_step2_on_success(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step1.store', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.step2', $episode));
    }

    public function test_store_saves_episode_id_to_wizard_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step1.store', $episode))
            ->assertSessionHas('wizard.generate_rss_feed.podcast_episode_id', $episode->id);
    }

    public function test_store_returns_403_for_another_users_episode(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($other);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step1.store', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_store_redirects_when_episode_has_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step1.store', $episode))
            ->assertRedirect(route('post_production.generate_rss_feed.index'));
    }

    public function test_store_does_not_change_episode_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->post(route('post_production.generate_rss_feed.step1.store', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed->value,
        ]);
    }
}