<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class CleanUpControllerTest extends TestCase
{
    use RefreshDatabase;

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  CONFIRM (GET)                                                         ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Unauthenticated users are redirected to the login page.
     */
    public function test_guest_cannot_view_confirm_page(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        $this->get(route('post_production.upload_production_audio.cleanup_confirm', $episode))
             ->assertRedirect(route('login'));
    }

    /**
     * Owner can view the confirm page and sees the expected filename.
     */
    public function test_owner_can_view_confirm_page(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.cleanup_confirm', $episode))
             ->assertOk()
             ->assertViewHas('expectedFilename', 'my-episode.mp3');
    }

    /**
     * Non-owner is redirected with an error.
     */
    public function test_non_owner_is_redirected_with_error_on_confirm(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.cleanup_confirm', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    /**
     * Wrong status is redirected with an error.
     */
    public function test_wrong_status_is_redirected_with_error_on_confirm(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.cleanup_confirm', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  DESTROY (POST)                                                        ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Destroy deletes the local file and redirects to the done page.
     */
    public function test_destroy_deletes_file_and_redirects_to_done_page(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $dir = storage_path('app/podcasts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/my-episode.mp3', 'fake mp3 content');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.done', $episode));

        $this->assertFileDoesNotExist($dir . '/my-episode.mp3');
    }

    /**
     * Destroy still redirects to the done page even if the file is not found.
     * Missing file is a soft failure — the pipeline must not be blocked.
     */
    public function test_destroy_succeeds_even_if_file_not_found(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $filePath = storage_path('app/podcasts/my-episode.mp3');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.done', $episode));
    }

    /**
     * Non-owner is redirected with an error.
     */
    public function test_non_owner_cannot_destroy(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    /**
     * Wrong status is redirected with an error.
     */
    public function test_wrong_status_is_redirected_with_error_on_destroy(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    /**
     * Unauthenticated users are redirected to the login page on destroy.
     */
    public function test_guest_cannot_destroy(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        $this->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('login'));
    }
}