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

    private function makeEpisode(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_publish): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
            'website_enabled' => false,
        ]);
    }

    public function test_publish_sets_website_enabled_and_advances_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

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

    public function test_publish_does_not_change_website_publish_on(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'            => $user->id,
            'podcast_show_id'    => $show->id,
            'status'             => PodcastEpisodeStatus::ready_to_publish,
            'website_enabled'    => false,
            'website_publish_on' => '2026-05-01',
        ]);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                 => $episode->id,
            'website_publish_on' => '2026-05-01',
        ]);
    }

    public function test_publish_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_publish_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    public function test_publish_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    public function test_publish_does_not_publish_when_status_is_wrong(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->post(route('post_production.publish_on_website.publish', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'              => $episode->id,
            'website_enabled' => false,
            'status'          => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }
}