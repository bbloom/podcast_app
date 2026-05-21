<?php

// =============================================================================
// IndexControllerTest
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class IndexControllerTest extends TestCase
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

    public function test_index_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk();
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('post_production.publish_on_website.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_shows_only_ready_to_publish_episodes(): void
    {
        $user = User::factory()->create();

        $ready    = $this->makeEpisode($user, PodcastEpisodeStatus::ready_to_publish);
        $notReady = $this->makeEpisode($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee($ready->title)
            ->assertDontSee($notReady->title);
    }

    public function test_index_shows_only_the_authenticated_users_episodes(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $mine   = $this->makeEpisode($user,  PodcastEpisodeStatus::ready_to_publish);
        $theirs = $this->makeEpisode($other, PodcastEpisodeStatus::ready_to_publish);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title);
    }

    public function test_index_shows_empty_state_when_no_episodes_are_ready(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee('No episodes are ready to publish.');
    }
}