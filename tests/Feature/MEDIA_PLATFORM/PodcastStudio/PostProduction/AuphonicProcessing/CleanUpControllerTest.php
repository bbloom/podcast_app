<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\AuphonicProcessing\Services\AuphonicService;
use Tests\TestCase;

class CleanUpControllerTest extends TestCase
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
     * Create a user, show, and episode in the given status with a known slug
     * and Auphonic production UUID.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();

        // Use a known slug so S3_work_in_progress_audio::getFolderPath()
        // can resolve it without throwing a RuntimeException.
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => $status,
            'raw_input_audio_filename' => 'episode-001.wav',
            'auphonic_production_uuid' => self::PRODUCTION_UUID,
        ]);
    }

    /**
     * Mock AuphonicService with a successful download, S3 delete, and
     * Auphonic production delete — the full happy path.
     */
    private function fakeExternalServices(): void
    {
        $this->mock(AuphonicService::class, function ($mock) {
            $mock->shouldReceive('downloadMp3')->once()->andReturn(storage_path('app/podcasts/episode-001.mp3'));
            $mock->shouldReceive('deleteS3Recording')->once()->andReturn(null);
            $mock->shouldReceive('deleteProduction')->once()->andReturn(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200)
                )
            );
        });
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  CONFIRM (GET)                                                         ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Confirm page returns 200 for the correct owner in auphonic_complete status.
     */
    public function test_confirm_returns_200_for_correct_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertOk()
            ->assertSee('Clean Up');
    }

    /**
     * Confirm page shows what will be deleted.
     */
    public function test_confirm_shows_deletion_details(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertOk()
            ->assertSee(self::PRODUCTION_UUID)
            ->assertSee($episode->raw_input_audio_filename);
    }

    /**
     * Confirm redirects with an error when the episode belongs to another user.
     */
    public function test_confirm_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $other);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Confirm redirects with an error when the episode has the wrong status.
     */
    public function test_confirm_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Confirm redirects unauthenticated users to the login page.
     */
    public function test_confirm_redirects_unauthenticated_users(): void
    {
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete);

        $this->get(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertRedirect(route('login'));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  DESTROY (POST)                                                        ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Destroy clears the auphonic_production_uuid on the episode.
     */
    public function test_destroy_clears_auphonic_production_uuid(): void
    {
        $this->fakeExternalServices();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                       => $episode->id,
            'auphonic_production_uuid' => null,
        ]);
    }

    /**
     * Destroy advances the episode status to ready_to_upload_production_file.
     */
    public function test_destroy_advances_status_to_ready_to_upload_production_file(): void
    {
        $this->fakeExternalServices();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file->value,
        ]);
    }

    /**
     * Destroy redirects to the index with a success flash.
     */
    public function test_destroy_redirects_to_index_with_success_flash(): void
    {
        $this->fakeExternalServices();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('success');
    }

    /**
     * Destroy redirects with an error when the episode belongs to another user.
     */
    public function test_destroy_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $other);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Destroy redirects with an error when the episode has the wrong status.
     */
    public function test_destroy_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Destroy redirects back to the confirm page with an error when the MP3
     * download fails. Nothing should be deleted.
     */
    public function test_destroy_redirects_to_confirm_with_error_when_download_fails(): void
    {
        $this->mock(AuphonicService::class, function ($mock) {
            $mock->shouldReceive('downloadMp3')->once()->andThrow(
                new \RuntimeException('Both Auphonic download endpoints failed.')
            );
            // deleteS3Recording and deleteProduction must NOT be called.
            $mock->shouldNotReceive('deleteS3Recording');
            $mock->shouldNotReceive('deleteProduction');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.cleanup_confirm', $episode))
            ->assertSessionHas('error');
    }

    /**
     * Destroy does not advance the episode status when the download fails.
     */
    public function test_destroy_does_not_advance_status_when_download_fails(): void
    {
        $this->mock(AuphonicService::class, function ($mock) {
            $mock->shouldReceive('downloadMp3')->once()->andThrow(
                new \RuntimeException('Both Auphonic download endpoints failed.')
            );
            $mock->shouldNotReceive('deleteS3Recording');
            $mock->shouldNotReceive('deleteProduction');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::auphonic_complete->value,
        ]);
    }

    /**
     * Destroy still advances the status even when the Auphonic delete returns 404.
     * The production may have already been deleted manually in the Auphonic console.
     */
    public function test_destroy_advances_status_even_when_auphonic_delete_returns_404(): void
    {
        $this->mock(AuphonicService::class, function ($mock) {
            $mock->shouldReceive('downloadMp3')->once()->andReturn(storage_path('app/podcasts/episode-001.mp3'));
            $mock->shouldReceive('deleteS3Recording')->once()->andReturn(null);
            $mock->shouldReceive('deleteProduction')->once()->andReturn(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(404)
                )
            );
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file->value,
        ]);
    }

    /**
     * Destroy still advances the status even when the S3 delete fails.
     * The pipeline must not be blocked by a partial clean-up failure.
     */
    public function test_destroy_advances_status_even_when_s3_delete_fails(): void
    {
        $this->mock(AuphonicService::class, function ($mock) {
            $mock->shouldReceive('downloadMp3')->once()->andReturn(storage_path('app/podcasts/episode-001.mp3'));
            $mock->shouldReceive('deleteS3Recording')->once()->andThrow(
                new \RuntimeException('S3 delete failed.')
            );
            $mock->shouldReceive('deleteProduction')->once()->andReturn(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200)
                )
            );
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.cleanup_destroy', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_to_upload_production_file->value,
        ]);
    }
}