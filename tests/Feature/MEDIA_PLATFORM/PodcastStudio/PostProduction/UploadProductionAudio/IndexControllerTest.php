<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\UploadProductionAudio;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('post_production.upload_production_audio.index'))
             ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_their_ready_episodes(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.index'))
             ->assertOk()
             ->assertViewHas('episodes', fn ($episodes) => $episodes->contains($episode));
    }

    public function test_episodes_belonging_to_other_users_are_not_shown(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.index'))
             ->assertOk()
             ->assertViewHas('episodes', fn ($episodes) => $episodes->isEmpty());
    }

    public function test_episodes_with_other_statuses_are_not_shown(): void
    {
        $user = User::factory()->create();

        PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::auphonic_complete,
        ]);

        $this->actingAs($user)
             ->get(route('post_production.upload_production_audio.index'))
             ->assertOk()
             ->assertViewHas('episodes', fn ($episodes) => $episodes->isEmpty());
    }
}