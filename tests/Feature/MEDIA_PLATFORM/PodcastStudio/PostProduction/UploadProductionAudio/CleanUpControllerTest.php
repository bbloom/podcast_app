<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class CleanUpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_confirm_page(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_generate_rss_feed,
        ]);

        $this->get(route('post_production.upload_production_audio.cleanup_confirm', $episode))
             ->assertRedirect(route('login'));
    }

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

    public function test_destroy_deletes_file_and_redirects_with_success(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        // Create the file on disk so the controller can delete it.
        $dir = storage_path('podcasts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/my-episode.mp3', 'fake mp3 content');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('success');

        $this->assertFileDoesNotExist($dir . '/my-episode.mp3');
    }

    public function test_destroy_succeeds_even_if_file_not_found(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_generate_rss_feed,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        // Ensure the file does not exist.
        $filePath = storage_path('podcasts/my-episode.mp3');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.cleanup', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('success');
    }

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
}