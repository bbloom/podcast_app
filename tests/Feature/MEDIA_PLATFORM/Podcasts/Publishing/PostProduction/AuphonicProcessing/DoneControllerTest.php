<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use Tests\TestCase;

class DoneControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Authenticated owner can view the done page.
     */
    public function test_owner_can_view_done_page(): void
    {
        $user    = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($user)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.done', $episode))
            ->assertOk()
            ->assertViewHas('episode');
    }

    /**
     * Unauthenticated users are redirected to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $episode = PodcastEpisode::factory()->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->get(route('post_production.auphonic_processing.done', $episode))
            ->assertRedirect(route('login'));
    }

    /**
     * A user cannot view the done page for another user's episode.
     */
    public function test_non_owner_is_redirected_with_error(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = PodcastEpisode::factory()->for($other)->create([
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file,
        ]);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.done', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }
}