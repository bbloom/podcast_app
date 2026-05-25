<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function episodeForUser(User $user, PodcastEpisodeStatus $status): PodcastEpisode
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
        $episode = $this->episodeForUser(
            User::factory()->create(),
            PodcastEpisodeStatus::ready_to_publish_website,
        );

        $this->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('login'));
    }

    // ── Ownership ────────────────────────────────────────────────────────────

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner, PodcastEpisodeStatus::ready_to_publish_website);

        $this->actingAs($other)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'))
            ->assertSessionHas('error');
    }

    // ── New pipeline status ───────────────────────────────────────────────────

    public function test_owner_with_ready_to_publish_website_status_sees_the_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish_website);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertOk()
            ->assertViewHas('episode');
    }

    // ── Legacy pipeline status ────────────────────────────────────────────────

    public function test_owner_with_legacy_ready_to_publish_status_still_sees_the_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertOk()
            ->assertViewHas('episode');
    }

    // ── Status guard ──────────────────────────────────────────────────────────

    public function test_wrong_status_is_redirected_with_error(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'))
            ->assertSessionHas('error');
    }

    public function test_published_episode_is_redirected(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::published);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}