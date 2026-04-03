<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\UploadRecording;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\UploadRecording\Exceptions\UploadRecordingException;
use MediaPlatform\PodcastStudio\PostProduction\UploadRecording\Services\UploadRecordingService;
use App\Models\User;
use Tests\TestCase;

class UploadRecordingControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // The session key used by the controller to pass the S3 object key
    // between store() and complete().
    // -------------------------------------------------------------------------
    private const SESSION_KEY = 'upload_recording.pending_key';

    // -------------------------------------------------------------------------
    // A realistic S3 key in the new format: {show-folder}/raw_input_files/{filename}
    // -------------------------------------------------------------------------
    private const PENDING_KEY = 'bobbloomshow/raw_input_files/my-recording.wav';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode with the given status, all linked together.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();
        $show = PodcastShow::factory()->create(['user_id' => $user->id]);

        return PodcastEpisode::factory()->create([
            'user_id'         => $user->id,
            'podcast_show_id' => $show->id,
            'status'          => $status,
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  INDEX                                                                 ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Index returns 200 and lists episodes that are ready to upload.
     */
    public function test_index_returns_200_and_lists_ready_episodes(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.index'))
            ->assertOk()
            ->assertSee($episode->title);
    }

    /**
     * Index excludes episodes that are not in the ready_to_upload_recording status.
     */
    public function test_index_excludes_episodes_with_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.index'))
            ->assertOk()
            ->assertDontSee($episode->title);
    }

    /**
     * Index excludes episodes belonging to other users.
     */
    public function test_index_excludes_other_users_episodes(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $other);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.index'))
            ->assertOk()
            ->assertDontSee($episode->title);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  SHOW                                                                  ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Show returns 200 for the correct owner with the correct status.
     */
    public function test_show_returns_200_for_correct_owner(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.show', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    /**
     * Show redirects with an error when the episode belongs to another user.
     */
    public function test_show_redirects_with_error_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $other);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.show', $episode))
            ->assertRedirect(route('post_production.upload_recording.index'))
            ->assertSessionHas('error');
    }

    /**
     * Show redirects with an error when the episode has the wrong status.
     */
    public function test_show_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.show', $episode))
            ->assertRedirect(route('post_production.upload_recording.index'))
            ->assertSessionHas('error');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  STORE (presign)                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Store returns JSON with a url key when the service succeeds.
     */
    public function test_store_returns_json_url_on_success(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('buildKey')
                ->once()
                ->andReturn(self::PENDING_KEY);

            $mock->shouldReceive('generatePresignedUrl')
                ->once()
                ->andReturn('https://s3.amazonaws.com/podcast_work_in_progress/' . self::PENDING_KEY . '?presigned=1');
        });

        $this->actingAs($user)
            ->postJson(route('post_production.upload_recording.store', $episode), [
                'filename' => 'my-recording.wav',
            ])
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    /**
     * Store stores the S3 key in the session (not returned to the browser).
     */
    public function test_store_stores_key_in_session(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('buildKey')
                ->once()
                ->andReturn(self::PENDING_KEY);

            $mock->shouldReceive('generatePresignedUrl')
                ->once()
                ->andReturn('https://example.com/presigned');
        });

        $this->actingAs($user)
            ->postJson(route('post_production.upload_recording.store', $episode), [
                'filename' => 'my-recording.wav',
            ])
            ->assertSessionHas(self::SESSION_KEY, self::PENDING_KEY);
    }

    /**
     * Store returns a JSON error when the service throws UploadRecordingException.
     */
    public function test_store_returns_json_error_when_service_throws(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('buildKey')
                ->once()
                ->andReturn(self::PENDING_KEY);

            $mock->shouldReceive('generatePresignedUrl')
                ->once()
                ->andThrow(new UploadRecordingException('S3 connection refused.'));
        });

        $this->actingAs($user)
            ->postJson(route('post_production.upload_recording.store', $episode), [
                'filename' => 'my-recording.wav',
            ])
            ->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    /**
     * Store returns 403 JSON when the episode belongs to another user.
     */
    public function test_store_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $other);

        $this->actingAs($user)
            ->postJson(route('post_production.upload_recording.store', $episode), [
                'filename' => 'my-recording.wav',
            ])
            ->assertStatus(403)
            ->assertJsonStructure(['error']);
    }

    /**
     * Store rejects filenames that do not end in .wav.
     */
    public function test_store_rejects_non_wav_filename(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->postJson(route('post_production.upload_recording.store', $episode), [
                'filename' => 'my-recording.mp3',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['filename']);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  COMPLETE                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Complete advances the episode status to ready_for_auphonic.
     */
    public function test_complete_advances_status_to_ready_for_auphonic(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }

    /**
     * Complete sets raw_input_audio_filename to the basename of the S3 key.
     */
    public function test_complete_sets_raw_input_audio_filename(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                       => $episode->id,
            'raw_input_audio_filename' => 'my-recording.wav',
        ]);
    }

    /**
     * Complete redirects to the post-production dashboard with a success flash.
     */
    public function test_complete_redirects_to_dashboard_with_success_flash(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertRedirect(route('post_production.dashboard'))
            ->assertSessionHas('success');
    }

    /**
     * Complete clears the session key after a successful upload.
     */
    public function test_complete_clears_session_key_on_success(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertSessionMissing(self::SESSION_KEY);
    }

    /**
     * Complete redirects back with an error when confirmFileExists throws.
     */
    public function test_complete_redirects_with_error_when_file_not_found_in_s3(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')
                ->once()
                ->andThrow(new UploadRecordingException('The uploaded file could not be found in S3.'));
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertRedirect(route('post_production.upload_recording.show', $episode))
            ->assertSessionHas('error');
    }

    /**
     * Complete redirects with an error when there is no pending key in the session.
     * This guards against the endpoint being called directly without going through store().
     */
    public function test_complete_redirects_with_error_when_no_session_key(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertRedirect(route('post_production.upload_recording.show', $episode))
            ->assertSessionHas('error');
    }

    /**
     * Complete redirects with an error when the episode belongs to another user.
     */
    public function test_complete_redirects_with_error_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $other);

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertRedirect(route('post_production.upload_recording.index'))
            ->assertSessionHas('error');
    }
}