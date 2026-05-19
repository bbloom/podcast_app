<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::ready_to_publish): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    public function test_show_renders_for_episode_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertOk();
    }

    public function test_show_redirects_unauthenticated_users(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $this->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_show_redirects_another_users_episode_to_index(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    public function test_show_redirects_wrong_status_to_index(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertRedirect(route('post_production.publish_on_website.index'));
    }

    public function test_show_displays_episode_title(): void
    {
        $user    = User::factory()->create();
        $show    = PodcastShow::factory()->create(['user_id' => $user->id]);
        $episode = PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => PodcastEpisodeStatus::ready_to_publish,
            'title'           => '#42 - My Great Episode',
        ]);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.show', $episode))
            ->assertOk()
            ->assertSee('#42 - My Great Episode');
    }
}