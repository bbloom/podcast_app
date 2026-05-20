<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Planning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PodcastEpisodePlanningLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeEpisode(User $user): PodcastEpisodePlanning
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);
        return PodcastEpisodePlanning::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
        ]);
    }

    private function makeLink(User $user, array $overrides = []): PodcastLink
    {
        return PodcastLink::factory()->create(array_merge([
            'user_id' => $user->id,
            'enabled' => true,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // attachIndex
    // -------------------------------------------------------------------------

    public function test_attach_index_shows_unattached_enabled_links_for_user(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode($user);

        $available = $this->makeLink($user,  ['title' => 'Available Link']);
        $disabled  = $this->makeLink($user,  ['title' => 'Disabled Link', 'enabled' => false]);
        $attached  = $this->makeLink($user,  ['title' => 'Attached Link']);
        $theirs    = $this->makeLink($other, ['title' => 'Other User Link']);

        $episode->links()->attach($attached->id);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.links.attach.index', $episode))
            ->assertOk()
            ->assertSee('Available Link')
            ->assertDontSee('Disabled Link')
            ->assertDontSee('Attached Link')
            ->assertDontSee('Other User Link');
    }

    public function test_attach_index_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);

        $this->actingAs($user)
            ->get(route('podcast_episodes_planning.links.attach.index', $ep))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_attach_index_redirects_unauthenticated_users(): void
    {
        $ep = $this->makeEpisode(User::factory()->create());
        $this->get(route('podcast_episodes_planning.links.attach.index', $ep))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // attach
    // -------------------------------------------------------------------------

    public function test_attach_links_a_link_to_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = $this->makeLink($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.links.attach', [$episode, $link]))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('podcast_link_episode_planning', [
            'podcast_episode_planning_id' => $episode->id,
            'podcast_link_id'             => $link->id,
        ]);
    }

    public function test_attach_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = $this->makeLink($user);

        $this->actingAs($user)->post(route('podcast_episodes_planning.links.attach', [$episode, $link]));
        $this->actingAs($user)->post(route('podcast_episodes_planning.links.attach', [$episode, $link]));

        $this->assertSame(1, \DB::table('podcast_link_episode_planning')
            ->where('podcast_episode_planning_id', $episode->id)
            ->where('podcast_link_id', $link->id)
            ->count());
    }

    public function test_attach_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);
        $link  = $this->makeLink($user);

        $this->actingAs($user)
            ->post(route('podcast_episodes_planning.links.attach', [$ep, $link]))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_attach_redirects_unauthenticated_users(): void
    {
        $user = User::factory()->create();
        $ep   = $this->makeEpisode($user);
        $link = $this->makeLink($user);

        $this->post(route('podcast_episodes_planning.links.attach', [$ep, $link]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // detach
    // -------------------------------------------------------------------------

    public function test_detach_removes_link_from_episode(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode($user);
        $link    = $this->makeLink($user);

        $episode->links()->attach($link->id);

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.links.detach', [$episode, $link]))
            ->assertRedirect(route('podcast_episodes_planning.show', $episode))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('podcast_link_episode_planning', [
            'podcast_episode_planning_id' => $episode->id,
            'podcast_link_id'             => $link->id,
        ]);
    }

    public function test_detach_redirects_with_error_for_another_users_episode(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $ep    = $this->makeEpisode($other);
        $link  = $this->makeLink($user);

        $this->actingAs($user)
            ->delete(route('podcast_episodes_planning.links.detach', [$ep, $link]))
            ->assertRedirect(route('podcast_episodes_planning.index'))
            ->assertSessionHas('error');
    }

    public function test_detach_redirects_unauthenticated_users(): void
    {
        $user = User::factory()->create();
        $ep   = $this->makeEpisode($user);
        $link = $this->makeLink($user);

        $this->delete(route('podcast_episodes_planning.links.detach', [$ep, $link]))
            ->assertRedirect(route('login'));
    }
}