<?php

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
        $this->get(route('post_production.publish_on_website.index'))
            ->assertRedirect(route('login'));
    }

    // ── New pipeline status ───────────────────────────────────────────────────

    public function test_shows_episodes_in_ready_to_publish_website_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish_website);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee($episode->title);
    }

    // ── Legacy pipeline status ────────────────────────────────────────────────

    public function test_still_shows_legacy_ready_to_publish_episodes(): void
    {
        $user    = User::factory()->create();
        $episode = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee($episode->title);
    }

    // ── Both statuses shown together ──────────────────────────────────────────

    public function test_shows_both_new_and_legacy_status_episodes_together(): void
    {
        $user     = User::factory()->create();
        $newPipe  = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish_website);
        $legacy   = $this->episodeForUser($user, PodcastEpisodeStatus::ready_to_publish);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertSee($newPipe->title)
            ->assertSee($legacy->title);
    }

    // ── Ownership isolation ───────────────────────────────────────────────────

    public function test_does_not_show_another_users_episodes(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $theirs = $this->episodeForUser($other, PodcastEpisodeStatus::ready_to_publish_website);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertDontSee($theirs->title);
    }

    // ── Irrelevant statuses hidden ────────────────────────────────────────────

    public function test_does_not_show_episodes_in_other_statuses(): void
    {
        $user      = User::factory()->create();
        $published = $this->episodeForUser($user, PodcastEpisodeStatus::published);
        $auphonic  = $this->episodeForUser($user, PodcastEpisodeStatus::ready_for_auphonic);

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk()
            ->assertDontSee($published->title)
            ->assertDontSee($auphonic->title);
    }

    // ── Renders cleanly with no episodes ─────────────────────────────────────

    public function test_renders_for_authenticated_user_with_no_episodes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('post_production.publish_on_website.index'))
            ->assertOk();
    }
}