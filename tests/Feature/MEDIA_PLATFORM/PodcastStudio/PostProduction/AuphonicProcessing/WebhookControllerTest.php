<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // The Auphonic production UUID stored on the episode.
    // -------------------------------------------------------------------------
    private const PRODUCTION_UUID = 'TestAuphonicUUID1234567890';

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode in processing_at_auphonic status
     * with a known Auphonic production UUID.
     */
    private function makeProcessingEpisode(?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => PodcastEpisodeStatus::processing_at_auphonic,
            'auphonic_production_uuid' => self::PRODUCTION_UUID,
        ]);
    }

    /**
     * POST to the webhook route with the given payload.
     */
    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('post_production.auphonic_processing.webhook'), $payload);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  WEBHOOK — DONE                                                        ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Webhook advances the episode status to auphonic_complete when Auphonic
     * reports status 3 (Done).
     */
    public function test_webhook_advances_status_to_auphonic_complete_when_done(): void
    {
        $episode = $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 3,
            'status_string' => 'Done',
        ]);

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::auphonic_complete->value,
        ]);
    }

    /**
     * Webhook returns HTTP 200 when Auphonic reports Done.
     */
    public function test_webhook_returns_200_when_done(): void
    {
        $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 3,
            'status_string' => 'Done',
        ])->assertOk();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  WEBHOOK — ERROR                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Webhook leaves the episode in processing_at_auphonic when Auphonic
     * reports status 2 (Error).
     */
    public function test_webhook_leaves_status_unchanged_when_auphonic_reports_error(): void
    {
        $episode = $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 2,
            'status_string' => 'Error',
        ]);

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::processing_at_auphonic->value,
        ]);
    }

    /**
     * Webhook returns HTTP 200 even when Auphonic reports an error.
     * This prevents Auphonic from retrying indefinitely.
     */
    public function test_webhook_returns_200_when_auphonic_reports_error(): void
    {
        $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 2,
            'status_string' => 'Error',
        ])->assertOk();
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  WEBHOOK — EDGE CASES                                                  ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Webhook returns 200 when the payload contains no UUID.
     */
    public function test_webhook_returns_200_when_uuid_is_missing(): void
    {
        $this->postWebhook([
            'status'        => 3,
            'status_string' => 'Done',
        ])->assertOk();
    }

    /**
     * Webhook returns 200 when the UUID does not match any episode.
     */
    public function test_webhook_returns_200_when_uuid_does_not_match_any_episode(): void
    {
        $this->postWebhook([
            'uuid'          => 'UnknownUUIDThatDoesNotExist',
            'status'        => 3,
            'status_string' => 'Done',
        ])->assertOk();
    }

    /**
     * Webhook is idempotent — a second Done call does not overwrite a status
     * that has already advanced beyond processing_at_auphonic.
     */
    public function test_webhook_is_idempotent_when_status_has_already_advanced(): void
    {
        $user = User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        // Episode is already auphonic_complete (webhook already fired once).
        $episode = PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => PodcastEpisodeStatus::auphonic_complete,
            'auphonic_production_uuid' => self::PRODUCTION_UUID,
        ]);

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 3,
            'status_string' => 'Done',
        ])->assertOk();

        // Status must not have changed.
        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::auphonic_complete->value,
        ]);
    }

    /**
     * Webhook returns 200 when the status code is unrecognised.
     */
    public function test_webhook_returns_200_for_unrecognised_status_code(): void
    {
        $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 99,
            'status_string' => 'Unknown',
        ])->assertOk();
    }

    /**
     * Webhook does not require authentication — it is called by Auphonic servers.
     */
    public function test_webhook_is_accessible_without_authentication(): void
    {
        $this->makeProcessingEpisode();

        $this->postWebhook([
            'uuid'          => self::PRODUCTION_UUID,
            'status'        => 3,
            'status_string' => 'Done',
        ])->assertOk();
    }
}