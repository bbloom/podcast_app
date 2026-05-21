<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\GenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a show and episode belonging to the given user with the given status.
     */
    private function episodeForUser(User $user, PodcastEpisodeStatus $status): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.index'))
            ->assertOk();
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('post_production.generate_rss_feed.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_shows_only_ready_to_generate_rss_feed_episodes(): void
    {
        $user = User::factory()->create();

        $ready   = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_generate_rss_feed);
        $notReady = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.index'))
            ->assertOk()
            ->assertSee($ready->title)
            ->assertDontSee($notReady->title);
    }

    public function test_index_shows_only_the_authenticated_users_episodes(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $mine   = $this->episodeForUser($user,  PodcastEpisodeStatus::ready_to_generate_rss_feed);
        $theirs = $this->episodeForUser($other, PodcastEpisodeStatus::ready_to_generate_rss_feed);

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.index'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title);
    }

    public function test_index_shows_empty_state_when_no_episodes_are_ready(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('post_production.generate_rss_feed.index'))
            ->assertOk()
            ->assertSee('No episodes are ready for RSS feed generation.');
    }
}