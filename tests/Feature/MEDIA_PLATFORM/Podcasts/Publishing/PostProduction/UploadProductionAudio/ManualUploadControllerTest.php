<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class ManualUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_manual_upload_form(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->get(route('post_production.upload_production_audio.manual_upload', $episode))
             ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_manual_upload_form(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.manual_upload', $episode))
             ->assertOk()
             ->assertViewHas('expectedFilename', 'my-episode.mp3');
    }

    public function test_non_owner_cannot_view_manual_upload_form(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.manual_upload', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_wrong_status_redirects_with_error_on_show(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::auphonic_complete,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.manual_upload', $episode))
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }

    public function test_upload_rejected_if_filename_stem_does_not_match(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $wrongFile = UploadedFile::fake()->create('wrong-name.mp3', 1024, 'audio/mpeg');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.manual_upload.store', $episode), [
                 'production_file' => $wrongFile,
             ])
             ->assertRedirect(route('post_production.upload_production_audio.manual_upload', $episode))
             ->assertSessionHas('error');
    }

    public function test_upload_accepted_when_filename_stem_matches(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $correctFile = UploadedFile::fake()->create('my-episode.mp3', 1024, 'audio/mpeg');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.manual_upload.store', $episode), [
                 'production_file' => $correctFile,
             ])
             ->assertRedirect(route('post_production.upload_production_audio.upload_to_storage', $episode))
             ->assertSessionHas('success');
    }

    public function test_store_rejects_non_mp3_file(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::ready_to_upload_production_file,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $wrongType = UploadedFile::fake()->create('my-episode.txt', 100, 'text/plain');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.manual_upload.store', $episode), [
                 'production_file' => $wrongType,
             ])
             ->assertSessionHasErrors('production_file');
    }

    public function test_store_rejects_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status'                   => PodcastEpisodeStatus::auphonic_complete,
            'raw_input_audio_filename' => 'my-episode.wav',
        ]);

        $file = UploadedFile::fake()->create('my-episode.mp3', 1024, 'audio/mpeg');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.manual_upload.store', $episode), [
                 'production_file' => $file,
             ])
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

        $file = UploadedFile::fake()->create('my-episode.mp3', 1024, 'audio/mpeg');

        $this->actingAs($user)
             ->post(route('post_production.upload_production_audio.manual_upload.store', $episode), [
                 'production_file' => $file,
             ])
             ->assertRedirect(route('post_production.upload_production_audio.index'))
             ->assertSessionHas('error');
    }
}