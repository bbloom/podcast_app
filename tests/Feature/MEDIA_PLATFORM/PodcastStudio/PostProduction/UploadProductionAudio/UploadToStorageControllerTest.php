<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class UploadToStorageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_upload_to_storage_page(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->get(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_upload_to_storage_page(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertOk()
             ->assertViewHas('expectedFilename', 'my-episode.mp3');
    }

    public function test_non_owner_is_redirected_with_error_on_show(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_wrong_status_is_redirected_with_error_on_show(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::auphonic_complete,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_store_redirects_with_error_if_file_missing_on_server(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        // Ensure the file does not exist on the server.
        $filePath = storage_path('podcasts/my-episode.mp3');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.upload_to_storage.store', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertSessionHas('error');
    }

    public function test_store_rejects_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::auphonic_complete,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.upload_to_storage.store', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_non_owner_cannot_store(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.upload_to_storage.store', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }
}