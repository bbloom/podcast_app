<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\PublishOnWebsite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class PrepareTriggerBuildsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function episodeForUser(User $user, PodcastEpisodeStatus $status = PodcastEpisodeStatus::website_published): PodcastEpisode
    {
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = $this->episodeForUser(User::factory()->create());

        $this->get(route('post_production.prepare_trigger_builds', $episode))
            ->assertRedirect(route('login'));
    }

    public function test_non_owner_is_redirected_with_error(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->episodeForUser($owner);

        $this->actingAs($other)
            ->get(route('post_production.prepare_trigger_builds', $episode))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_wrong_status_is_redirected_to_correct_pipeline_step(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.prepare_trigger_builds', $episode))
            ->assertRedirect(route(
                PodcastEpisodeStatus::ready_for_auphonic->postProductionShowRoute(),
                $episode,
            ))
            ->assertSessionHas('error');
    }

    public function test_stores_episode_id_in_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.prepare_trigger_builds', $episode));

        $this->assertEquals(
            $episode->id,
            session('build_confirmation.pending_episode_id'),
        );
    }

    public function test_redirects_to_trigger_builds_select_for_the_episodes_show(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.prepare_trigger_builds', $episode))
            ->assertRedirect(route('post_production.trigger_builds.select', $episode->show));
    }

    public function test_episode_status_is_not_changed(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user);

        $this->actingAs($user)
            ->get(route('post_production.prepare_trigger_builds', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::website_published->value,
        ]);
    }
}