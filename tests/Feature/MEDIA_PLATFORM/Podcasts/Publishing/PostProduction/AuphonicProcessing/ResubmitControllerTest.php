<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class ResubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // The existing Auphonic production UUID on the episode before re-submit.
    // -------------------------------------------------------------------------
    private const OLD_PRODUCTION_UUID = 'OldAuphonicUUID1234567890';

    // -------------------------------------------------------------------------
    // The new Auphonic production UUID returned after re-submit.
    // -------------------------------------------------------------------------
    private const NEW_PRODUCTION_UUID = 'NewAuphonicUUID0987654321';

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode in the given status with a known
     * Auphonic production UUID.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();

        // Use a known slug so Auphonic_preset::getPreset() and
        // S3_work_in_progress_audio::getFolderPath() can resolve it.
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => $status,
            'raw_input_audio_filename' => 'episode-001.wav',
            'auphonic_production_uuid' => self::OLD_PRODUCTION_UUID,
        ]);
    }

    /**
     * Fake successful Auphonic delete and re-submit API responses.
     */
    private function fakeAuphonicSuccess(): void
    {
        Http::fake([
            '*auphonic.com/api/production/' . self::OLD_PRODUCTION_UUID . '.json' => Http::response([], 200),
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code'   => 200,
                'error_message' => '',
                'data'          => ['uuid' => self::NEW_PRODUCTION_UUID],
            ], 200),
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  CONFIRM (GET)                                                         ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Confirm page returns 200 for the correct owner in auphonic_complete status.
     */
    public function test_confirm_returns_200_for_correct_owner_in_auphonic_complete(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertOk()
            ->assertSee('Confirm Re-submission');
    }

    /**
     * Confirm page returns 200 for the correct owner in processing_at_auphonic status.
     */
    public function test_confirm_returns_200_for_correct_owner_in_processing_at_auphonic(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertOk()
            ->assertSee('Confirm Re-submission');
    }

    /**
     * Confirm page shows the episode title and existing production UUID.
     */
    public function test_confirm_shows_episode_and_production_details(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertOk()
            ->assertSee($episode->title)
            ->assertSee(self::OLD_PRODUCTION_UUID);
    }

    /**
     * Confirm cancel link points to the complete page when status is auphonic_complete.
     */
    public function test_confirm_cancel_link_points_to_complete_page_when_auphonic_complete(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertOk()
            ->assertSee(route('post_production.auphonic_processing.complete', $episode));
    }

    /**
     * Confirm cancel link points to the show (processing) page when status is processing_at_auphonic.
     */
    public function test_confirm_cancel_link_points_to_show_page_when_processing_at_auphonic(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertOk()
            ->assertSee(route('post_production.auphonic_processing.show', $episode));
    }

    /**
     * Confirm redirects with an error when the episode belongs to another user.
     */
    public function test_confirm_redirects_with_error_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $other);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Confirm redirects with an error when the episode has an invalid status.
     */
    public function test_confirm_redirects_with_error_for_invalid_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Confirm redirects unauthenticated users to the login page.
     */
    public function test_confirm_redirects_unauthenticated_users(): void
    {
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete);

        $this->get(route('post_production.auphonic_processing.resubmit_confirm', $episode))
            ->assertRedirect(route('login'));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  RESUBMIT (POST)                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Resubmit clears the old UUID and stores the new one on the episode.
     */
    public function test_resubmit_stores_new_auphonic_production_uuid(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'                       => $episode->id,
            'auphonic_production_uuid' => self::NEW_PRODUCTION_UUID,
        ]);
    }

    /**
     * Resubmit resets the episode status to processing_at_auphonic.
     */
    public function test_resubmit_resets_status_to_processing_at_auphonic(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::processing_at_auphonic->value,
        ]);
    }

    /**
     * Resubmit renders the processing view on success.
     */
    public function test_resubmit_renders_processing_view_on_success(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');
    }

    /**
     * Resubmit can also be triggered from processing_at_auphonic status
     * (the Auphonic error scenario).
     */
    public function test_resubmit_works_from_processing_at_auphonic_status(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');
    }

    /**
     * Resubmit redirects with an error when the episode belongs to another user.
     */
    public function test_resubmit_redirects_with_error_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $other);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Resubmit redirects with an error when the episode has an invalid status.
     */
    public function test_resubmit_redirects_with_error_for_invalid_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Resubmit redirects with an error when the Auphonic API returns an error
     * on the new submission.
     */
    public function test_resubmit_redirects_with_error_when_new_submission_fails(): void
    {
        Http::fake([
            '*auphonic.com/api/production/' . self::OLD_PRODUCTION_UUID . '.json' => Http::response([], 200),
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code'   => 400,
                'error_message' => 'URL does not exist.',
                'data'          => [],
            ], 400),
        ]);

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.show', $episode))
            ->assertSessionHas('error');
    }

    /**
     * Resubmit proceeds with the new submission even when the delete returns 404
     * (production already deleted in Auphonic console).
     */
    public function test_resubmit_proceeds_when_delete_returns_404(): void
    {
        Http::fake([
            '*auphonic.com/api/production/' . self::OLD_PRODUCTION_UUID . '.json' => Http::response([], 404),
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code'   => 200,
                'error_message' => '',
                'data'          => ['uuid' => self::NEW_PRODUCTION_UUID],
            ], 200),
        ]);

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.resubmit', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::processing_at_auphonic->value,
        ]);
    }
}