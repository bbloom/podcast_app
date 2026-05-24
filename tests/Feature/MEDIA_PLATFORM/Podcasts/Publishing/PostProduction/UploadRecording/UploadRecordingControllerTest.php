<?php

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\UploadRecording;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Exceptions\UploadRecordingException;
use MediaPlatform\Podcasts\Publishing\PostProduction\UploadRecording\Services\UploadRecordingService;
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
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $other);

        $this->actingAs($user)
            ->get(route('post_production.upload_recording.index'))
            ->assertOk();
    }

    /**
     * Index redirects unauthenticated users to the login page.
     */
    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('post_production.upload_recording.index'))
            ->assertRedirect(route('login'));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  COMPLETE                                                              ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Complete redirects to the done page on success.
     */
    public function test_complete_redirects_to_done_page(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->mock(UploadRecordingService::class, function ($mock) {
            $mock->shouldReceive('confirmFileExists')->once()->andReturn(true);
        });

        $this->actingAs($user)
            ->withSession([self::SESSION_KEY => self::PENDING_KEY])
            ->post(route('post_production.upload_recording.complete', $episode))
            ->assertRedirect(route('post_production.upload_recording.done', $episode));
    }

    /**
     * Complete sets raw_input_audio_filename from the session key basename.
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

        $this->assertDatabaseHas('podcast_episodes_published', [
            'id'                       => $episode->id,
            'raw_input_audio_filename' => 'my-recording.wav',
        ]);
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