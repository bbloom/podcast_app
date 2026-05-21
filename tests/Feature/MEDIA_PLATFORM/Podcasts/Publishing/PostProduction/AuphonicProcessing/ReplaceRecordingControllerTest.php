<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Tests\TestCase;

class ReplaceRecordingControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode in the given status.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();

        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => $status,
            'raw_input_audio_filename' => 'bobbloomshow47.wav',
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  REPLACE RECORDING (POST)                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Resets the episode status to ready_to_upload_recording.
     */
    public function test_resets_status_to_ready_to_upload_recording(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.replace_recording', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_upload_recording->value,
        ]);
    }

    /**
     * Does not modify raw_input_audio_filename — the expected filename is preserved.
     */
    public function test_does_not_modify_raw_input_audio_filename(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.replace_recording', $episode));

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'                       => $episode->id,
            'raw_input_audio_filename' => 'bobbloomshow47.wav',
        ]);
    }

    /**
     * Redirects to the upload recording show page with a success flash.
     */
    public function test_redirects_to_upload_recording_show_with_success_flash(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.replace_recording', $episode))
            ->assertRedirect(route('post_production.upload_recording.show', $episode))
            ->assertSessionHas('success');
    }

    /**
     * Redirects with an error when the episode belongs to another user.
     */
    public function test_redirects_with_error_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $other);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.replace_recording', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Redirects with an error when the episode has the wrong status.
     */
    public function test_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.replace_recording', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Redirects unauthenticated users to the login page.
     */
    public function test_redirects_unauthenticated_users(): void
    {
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic);

        $this->post(route('post_production.auphonic_processing.replace_recording', $episode))
            ->assertRedirect(route('login'));
    }
}