<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class PublishControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create an episode with a publish date in the past or today (due now).
     * Publishing this episode redirects to Trigger Static Site Builds.
     */
    private function makeEpisodeDueNow(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_publish): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'status'             => $status,
            'website_enabled'    => false,
            'website_publish_on' => now()->toDateString(),
        ]);
    }

    /**
     * Create an episode with a future publish date.
     * Publishing this episode redirects to the index — no build trigger needed yet.
     */
    private function makeEpisodeFutureDated(User $user): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'status'             => PodcastEpisodeStatus::ready_to_publish,
            'website_enabled'    => false,
            'website_publish_on' => now()->addMonth()->toDateString(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Happy path — episode due now (redirects to trigger builds)
    // -------------------------------------------------------------------------

    public function test_publish_sets_website_enabled_and_advances_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeDueNow($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.trigger_builds.select', $episode->show))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'website_enabled' => true,
            'status'          => PodcastEpisodeStatus::published->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Happy path — future-dated episode (redirects to index)
    // -------------------------------------------------------------------------

    public function test_publish_future_dated_episode_redirects_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeFutureDated($user);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'website_enabled' => true,
            'status'          => PodcastEpisodeStatus::published->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // website_publish_on is never changed
    // -------------------------------------------------------------------------

    public function test_publish_does_not_change_website_publish_on(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'status'             => PodcastEpisodeStatus::ready_to_publish,
            'website_enabled'    => false,
            'website_publish_on' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                 => $episode->id,
            'website_publish_on' => now()->addMonth()->toDateString(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Auth + ownership
    // -------------------------------------------------------------------------

    public function test_publish_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeDueNow($user);

        $this->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_publish_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisodeDueNow($other);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    // -------------------------------------------------------------------------
    // Status guard
    // -------------------------------------------------------------------------

    public function test_publish_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeDueNow($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    public function test_publish_does_not_publish_when_status_is_wrong(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisodeDueNow($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'website_enabled' => false,
            'status'          => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }
}