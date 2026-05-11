<?php

namespace Tests\Feature\MEDIA_PLATFORM\PodcastStudio\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\PodcastStudio\Management\Enums\PodcastEpisodeStatus;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\CloudStorage\S3_work_in_progress_audio;
use Tests\TestCase;


class SubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // A fake Auphonic production UUID returned by the mocked API response.
    // -------------------------------------------------------------------------
    private const FAKE_PRODUCTION_UUID = 'TestAuphonicUUID1234567890';

    // -------------------------------------------------------------------------
    // The expected WAV filename — matches what Step3Controller would generate.
    // -------------------------------------------------------------------------
    private const EXPECTED_FILENAME = 'episode-001.wav';


    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*auphonic.com/api/user.json*' => Http::response([
                'status_code' => 200,
                'data' => [
                    'credits'           => 5.0,
                    'onetime_credits'   => 2.0,
                    'recurring_credits' => 3.0,
                    'recharge_date'     => '2026-06-15T00:00:00Z',
                ],
            ], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode linked together with the given status.
     */
    private function makeEpisode(PodcastEpisodeStatus $status, ?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();

        // Use a known slug so Auphonic_preset::getPreset() and
        // S3_work_in_progress_audio::getFolderPath() can resolve it.
        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'status'                   => $status,
            'raw_input_audio_filename' => self::EXPECTED_FILENAME,
        ]);
    }

    /**
     * Mock S3_work_in_progress_audio::listFiles() to return a matching file.
     * Used in show() tests where we want the happy-path S3 check.
     */
    private function fakeS3Match(): void
    {
        $this->mock(S3_work_in_progress_audio::class, function ($mock) {
            $mock->shouldReceive('listFiles')->andReturn([self::EXPECTED_FILENAME]);
            $mock->shouldReceive('buildConsoleUrl')->andReturn('https://s3.console.aws.amazon.com/s3/buckets/podcast-work-in-progress');
            $mock->shouldReceive('getFolderPath')->andReturn('bobbloomshow/raw_input_files/');
            $mock->shouldReceive('getBucket')->andReturn('podcast-work-in-progress');
        });
    }

    /**
     * Fake a successful Auphonic API response.
     */
    private function fakeAuphonicSuccess(): void
    {
        Http::fake([
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code'   => 200,
                'error_message' => '',
                'data'          => [
                    'uuid' => self::FAKE_PRODUCTION_UUID,
                ],
            ], 200),
        ]);
    }

    /**
     * Fake a failed Auphonic API response.
     */
    private function fakeAuphonicError(string $message = 'URL does not exist.'): void
    {
        Http::fake([
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code'   => 400,
                'error_message' => $message,
                'data'          => [],
            ], 400),
        ]);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  SHOW — S3 CHECK                                                       ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Show renders the Submit button when S3 contains the expected file.
     */
    public function test_show_renders_submit_button_when_s3_file_matches(): void
    {
        $this->fakeS3Match();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('Submit to Auphonic')
            ->assertSee('confirmed in S3');
    }

    /**
     * Show hides the Submit button and shows a warning when S3 is empty.
     */
    public function test_show_shows_warning_when_s3_is_empty(): void
    {
        $this->mock(S3_work_in_progress_audio::class, function ($mock) {
            $mock->shouldReceive('listFiles')->andReturn([]);
            $mock->shouldReceive('buildConsoleUrl')->andReturn('https://s3.console.aws.amazon.com/s3/buckets/podcast-work-in-progress');
            $mock->shouldReceive('getFolderPath')->andReturn('bobbloomshow/raw_input_files/');
            $mock->shouldReceive('getBucket')->andReturn('podcast-work-in-progress');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('No recording file was found in S3')
            ->assertDontSee('post_production.auphonic_processing.submit');
    }

    /**
     * Show hides the Submit button and shows a warning when S3 has a mismatched file.
     */
    public function test_show_shows_warning_when_s3_file_mismatches(): void
    {
        $this->mock(S3_work_in_progress_audio::class, function ($mock) {
            $mock->shouldReceive('listFiles')->andReturn(['wrong-episode.wav']);
            $mock->shouldReceive('buildConsoleUrl')->andReturn('https://s3.console.aws.amazon.com/s3/buckets/podcast-work-in-progress');
            $mock->shouldReceive('getFolderPath')->andReturn('bobbloomshow/raw_input_files/');
            $mock->shouldReceive('getBucket')->andReturn('podcast-work-in-progress');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('does not match the expected recording')
            ->assertSee('wrong-episode.wav')
            ->assertDontSee('post_production.auphonic_processing.submit');
    }

    /**
     * Show hides the Submit button and shows a warning when multiple files are in S3.
     */
    public function test_show_shows_warning_when_multiple_s3_files_found(): void
    {
        $this->mock(S3_work_in_progress_audio::class, function ($mock) {
            $mock->shouldReceive('listFiles')->andReturn(['episode-001.wav', 'episode-002.wav']);
            $mock->shouldReceive('buildConsoleUrl')->andReturn('https://s3.console.aws.amazon.com/s3/buckets/podcast-work-in-progress');
            $mock->shouldReceive('getFolderPath')->andReturn('bobbloomshow/raw_input_files/');
            $mock->shouldReceive('getBucket')->andReturn('podcast-work-in-progress');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('Multiple files were found in S3')
            ->assertDontSee('post_production.auphonic_processing.submit');
    }

    /**
     * Show includes the AWS console link in all non-match states.
     */
    public function test_show_includes_aws_console_link_when_s3_check_fails(): void
    {
        $this->mock(S3_work_in_progress_audio::class, function ($mock) {
            $mock->shouldReceive('listFiles')->andReturn([]);
            $mock->shouldReceive('buildConsoleUrl')->andReturn('https://s3.console.aws.amazon.com/s3/buckets/podcast-work-in-progress');
            $mock->shouldReceive('getFolderPath')->andReturn('bobbloomshow/raw_input_files/');
            $mock->shouldReceive('getBucket')->andReturn('podcast-work-in-progress');
        });

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('s3.console.aws.amazon.com');
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  SHOW — EXISTING BEHAVIOUR                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Show returns 200 for the episode owner when status is ready_for_auphonic.
     */
    public function test_show_returns_200_for_correct_owner(): void
    {
        $this->fakeS3Match();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee($episode->title);
    }

    /**
     * Show redirects with an error when the episode belongs to another user.
     */
    public function test_show_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $other);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Show renders the processing view when the episode is already processing.
     */
    public function test_show_renders_processing_view_when_already_processing(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');
    }

    /**
     * Show redirects to the complete page when status is auphonic_complete.
     */
    public function test_show_redirects_to_complete_when_auphonic_complete(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::auphonic_complete, $user);

        $this->actingAs($user)
            ->get(route('post_production.auphonic_processing.show', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.complete', $episode));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  SUBMIT                                                                ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * Submit calls the Auphonic API and stores the production UUID on the episode.
     */
    public function test_submit_stores_auphonic_production_uuid(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'                       => $episode->id,
            'auphonic_production_uuid' => self::FAKE_PRODUCTION_UUID,
        ]);
    }

    /**
     * Submit advances the episode status to processing_at_auphonic.
     */
    public function test_submit_advances_status_to_processing_at_auphonic(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::processing_at_auphonic->value,
        ]);
    }

    /**
     * Submit renders the processing view on success.
     *
     * 
     * test_submit_renders_processing_view_without_api_call_when_already_processing calls
     * Http::fake() with no arguments (which fakes everything) and then asserts
     * Http::assertNothingSent(). That will still pass because the episode is in 
     * processing_at_auphonic status, so the controller returns the processing view before
     * reaching fetchCredits(). 
     */
    public function test_submit_renders_processing_view_on_success(): void
    {
        $this->fakeAuphonicSuccess();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');
    }

    /**
     * Submit redirects with an error when the episode belongs to another user.
     */
    public function test_submit_returns_403_for_wrong_owner(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $other);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Submit redirects with an error when the episode has the wrong status.
     */
    public function test_submit_redirects_with_error_for_wrong_status(): void
    {
        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_to_upload_recording, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.index'))
            ->assertSessionHas('error');
    }

    /**
     * Submit renders the processing view without a new API call when already processing.
     */
    public function test_submit_renders_processing_view_without_api_call_when_already_processing(): void
    {
        Http::fake();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::processing_at_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertOk()
            ->assertSee('Processing at Auphonic');

        Http::assertNothingSent();
    }

    /**
     * Submit redirects with an error when Auphonic returns a non-success response.
     */
    public function test_submit_redirects_with_error_when_auphonic_returns_error(): void
    {
        $this->fakeAuphonicError('URL does not exist.');

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.show', $episode))
            ->assertSessionHas('error');
    }

    /**
     * Submit does not advance the status when Auphonic returns an error.
     */
    public function test_submit_does_not_advance_status_when_auphonic_returns_error(): void
    {
        $this->fakeAuphonicError();

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode));

        $this->assertDatabaseHas('podcast_episodes', [
            'id'     => $episode->id,
            'status' => PodcastEpisodeStatus::ready_for_auphonic->value,
        ]);
    }

    /**
     * Submit redirects with an error when Auphonic response contains no UUID.
     */
    public function test_submit_redirects_with_error_when_uuid_missing_from_response(): void
    {
        Http::fake([
            '*auphonic.com/api/productions.json' => Http::response([
                'status_code' => 200,
                'data'        => [],
            ], 200),
        ]);

        $user    = User::factory()->create();
        $episode = $this->makeEpisode(PodcastEpisodeStatus::ready_for_auphonic, $user);

        $this->actingAs($user)
            ->post(route('post_production.auphonic_processing.submit', $episode))
            ->assertRedirect(route('post_production.auphonic_processing.show', $episode))
            ->assertSessionHas('error');
    }
}