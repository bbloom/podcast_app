<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class CompleteControllerTest extends TestCase
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
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => $status,
            'auphonic_production_uuid' => 'TestAuphonicUUID1234567890',
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  COMPLETE                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Complete returns 200 and shows the "Done!" view for the correct owner
     * when the episode is in auphonic_complete status.
     */
    public function test_complete_returns_200_for_correct_owner_and_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.complete', $episode))
            ->assertOk()
            ->assertSee('What would you like to do?');
    }

    /**
     * Complete shows the episode title on the Done page.
     */
    public function test_complete_shows_episode_title(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.complete', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    /**
     * Complete returns 403 when the episode belongs to another user.
     */
    public function test_complete_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $other);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.complete', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Complete redirects with an error when the episode is not in auphonic_complete status.
     */
    public function test_complete_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.complete', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Complete redirects unauthenticated users to the login page.
     */
    public function test_complete_redirects_unauthenticated_users(): void
    {
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete);

        $this->get(route('post_production.auphonic_processing.complete', $episode))
            ->assertRedirect(route('login'));
    }
}