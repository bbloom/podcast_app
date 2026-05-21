<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\AuphonicProcessing;

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
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode linked together with the given status.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  INDEX                                                                 ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Index returns 200 and lists episodes with ready_for_auphonic status.
     */
    public function test_index_returns_200_and_lists_ready_episodes(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.index'))
            ->assertOk()
            ->assertSee($episode->title);
    }

    /**
     * Index shows the empty state when no episodes are ready.
     */
    public function test_index_shows_empty_state_when_no_episodes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.index'))
            ->assertOk()
            ->assertSee('No episodes are ready for Auphonic');
    }

    /**
     * Index excludes episodes that are not in the ready_for_auphonic status.
     */
    public function test_index_excludes_episodes_with_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.index'))
            ->assertOk()
            ->assertDontSee($episode->title);
    }

    /**
     * Index excludes episodes belonging to other users.
     */
    public function test_index_excludes_other_users_episodes(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $other);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.index'))
            ->assertOk()
            ->assertDontSee($episode->title);
    }

    /**
     * Index redirects unauthenticated users to the login page.
     */
    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('post_production.auphonic_processing.index'))
            ->assertRedirect(route('login'));
    }
}