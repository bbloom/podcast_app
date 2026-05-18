<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\RegenerateRssFeed;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('post_production.regenerate_rss_feed.index'))
            ->assertOk();
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('post_production.regenerate_rss_feed.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_shows_only_the_authenticated_users_shows(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        PodcastShow::factory()->create(['user_id' => $user->id,  'title' => 'My Show']);
        PodcastShow::factory()->create(['user_id' => $other->id, 'title' => 'Their Show']);

        $this->actingAs($user)
            ->get(route('post_production.regenerate_rss_feed.index'))
            ->assertOk()
            ->assertSee('My Show')
            ->assertDontSee('Their Show');
    }

    public function test_index_shows_empty_state_when_user_has_no_shows(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('post_production.regenerate_rss_feed.index'))
            ->assertOk()
            ->assertSee('No podcast shows found.');
    }
}