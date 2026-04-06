<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->get(route('post_production.upload_production_audio.show', $episode))
             ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_decision_page(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.show', $episode))
             ->assertOk()
             ->assertViewHas('expectedFilename', 'my-episode.mp3');
    }

    public function test_non_owner_is_redirected_with_error(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.show', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_wrong_status_is_redirected_with_error(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::auphonic_complete,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.show', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }
}