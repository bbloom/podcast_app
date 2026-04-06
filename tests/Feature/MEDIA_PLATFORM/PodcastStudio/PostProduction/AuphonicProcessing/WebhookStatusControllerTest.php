<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use Tests\TestCase;

class WebhookStatusControllerTest extends TestCase
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
    // ║  WEBHOOK STATUS                                                        ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Returns JSON with the correct status value when processing.
     */
    public function test_returns_json_with_status_when_processing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->getJson(route('post_production.auphonic_processing.webhook_status', $episode))
            ->assertOk()
            ->assertJson([
                'status'   => PodcastEpisodeStatus::processing_at_auphonic->value,
                'complete' => false,
            ]);
    }

    /**
     * Returns complete=true when the episode status is auphonic_complete.
     */
    public function test_returns_complete_true_when_auphonic_complete(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->getJson(route('post_production.auphonic_processing.webhook_status', $episode))
            ->assertOk()
            ->assertJson([
                'status'   => PodcastEpisodeStatus::auphonic_complete->value,
                'complete' => true,
            ]);
    }

    /**
     * Returns complete=false for any status other than auphonic_complete.
     */
    public function test_returns_complete_false_for_non_complete_statuses(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->getJson(route('post_production.auphonic_processing.webhook_status', $episode))
            ->assertOk()
            ->assertJson(['complete' => false]);
    }

    /**
     * Returns 403 when the episode belongs to another user.
     */
    public function test_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $other);

        $this->actingAs($user)
            ->getJson(route('post_production.auphonic_processing.webhook_status', $episode))
            ->assertForbidden();
    }

    /**
     * Redirects unauthenticated users to the login page.
     */
    public function test_redirects_unauthenticated_users(): void
    {
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic);

        $this->getJson(route('post_production.auphonic_processing.webhook_status', $episode))
            ->assertUnauthorized();
    }
}