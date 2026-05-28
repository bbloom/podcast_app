<?php

// =============================================================================
// AuphonicServiceTest
//
// Unit tests for AuphonicService.
//
// External HTTP calls are faked using Laravel's Http::fake(). Filesystem
// operations in downloadMp3() write to storage_path('app/podcasts/') — the
// directory is created by the service itself, so no test setup is needed.
// Files written during tests are cleaned up in tearDown().
//
// Path: tests/Feature/MEDIA_PLATFORM/Podcasts/PostProduction/AuphonicProcessing/
// =============================================================================

namespace Tests\Feature\MEDIA_PLATFORM\Podcasts\Publishing\PostProduction\AuphonicProcessing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\Publishing\PostProduction\AuphonicProcessing\Services\AuphonicService;
use Tests\TestCase;

class AuphonicServiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // The Auphonic production UUID used across tests.
    // -------------------------------------------------------------------------
    private const PRODUCTION_UUID = 'TestAuphonicUUID1234567890';

    // -------------------------------------------------------------------------
    // Fake MP3 content written to disk by download tests.
    // -------------------------------------------------------------------------
    private const FAKE_MP3_CONTENT = 'fake-mp3-binary-content';

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Create a user, show, and episode with a known slug and production UUID.
     */
    private function makeEpisode(?User $user = null): PodcastEpisode
    {
        $user = $user ?? User::factory()->create();

        $show = PodcastShow::factory()->create([
            'user_id' => $user->id,
            'slug'    => 'bob-bloom-show',
        ]);

        return PodcastEpisode::factory()->create([
            'user_id'                  => $user->id,
            'podcast_show_id'          => $show->id,
            'raw_input_audio_filename' => 'episode-001.wav',
            'auphonic_production_uuid' => self::PRODUCTION_UUID,
        ]);
    }

    /**
     * Remove any MP3 files written to storage_path('app/podcasts/') during tests.
     */
    protected function tearDown(): void
    {
        $dir = storage_path('app/podcasts');

        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $entry) {
                if (is_file($entry)) {
                    unlink($entry);
                } elseif (is_dir($entry)) {
                    foreach (glob($entry . '/*') as $subEntry) {
                        if (is_file($subEntry)) {
                            unlink($subEntry);
                        }
                    }
                    rmdir($entry);
                }
            }

            if (count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }

        parent::tearDown();
    }

    // =========================================================================
    // SERVICE INSTANCE
    // =========================================================================

    /**
     * Resolve a fresh AuphonicService from the container.
     */
    private function service(): AuphonicService
    {
        return app(AuphonicService::class);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  buildMp3Filename()                                                    ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * buildMp3Filename() replaces the .wav extension with .mp3.
     */
    public function test_build_mp3_filename_replaces_wav_extension_with_mp3(): void
    {
        $episode = $this->makeEpisode();

        $filename = $this->service()->buildMp3Filename($episode);

        $this->assertSame('episode-001.mp3', $filename);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  buildDownloadUrl()                                                    ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * buildDownloadUrl() returns the /engine/ URL containing the production
     * UUID and the derived MP3 filename.
     */
    public function test_build_download_url_returns_engine_url_with_uuid_and_filename(): void
    {
        $episode = $this->makeEpisode();

        $url = $this->service()->buildDownloadUrl($episode);

        $this->assertStringContainsString(self::PRODUCTION_UUID, $url);
        $this->assertStringContainsString('episode-001.mp3', $url);
        $this->assertStringContainsString('auphonic.com/engine/download/audio-result', $url);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  buildAuphonicConsoleUrl()                                             ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * buildAuphonicConsoleUrl() returns a URL containing the production UUID.
     */
    public function test_build_auphonic_console_url_contains_production_uuid(): void
    {
        $url = $this->service()->buildAuphonicConsoleUrl(self::PRODUCTION_UUID);

        $this->assertStringContainsString(self::PRODUCTION_UUID, $url);
        $this->assertStringContainsString('auphonic.com', $url);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  downloadMp3() — happy paths                                           ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * downloadMp3() saves the MP3 to storage_path('app/podcasts/') and returns
     * the absolute path when the /engine/ endpoint succeeds.
     */
    public function test_download_mp3_saves_file_and_returns_path_on_engine_success(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $episode = $this->makeEpisode();
        $path    = $this->service()->downloadMp3($episode);

        $this->assertFileExists($path);
        $this->assertSame(self::FAKE_MP3_CONTENT, file_get_contents($path));
        $this->assertStringContainsString('episode-001.mp3', $path);
    }

    /**
     * downloadMp3() falls back to the /api/ endpoint when the /engine/
     * endpoint returns a non-success status, and still saves the file.
     */
    public function test_download_mp3_falls_back_to_api_endpoint_when_engine_fails(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response('', 503),
            '*auphonic.com/api/download/audio-result/*'    => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $episode = $this->makeEpisode();
        $path    = $this->service()->downloadMp3($episode);

        $this->assertFileExists($path);
        $this->assertSame(self::FAKE_MP3_CONTENT, file_get_contents($path));
    }

    /**
     * downloadMp3() falls back to the /api/ endpoint when the /engine/
     * endpoint throws a network-level exception.
     */
    public function test_download_mp3_falls_back_to_api_endpoint_when_engine_throws(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => function () {
                throw new \RuntimeException('Connection refused.');
            },
            '*auphonic.com/api/download/audio-result/*' => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $episode = $this->makeEpisode();
        $path    = $this->service()->downloadMp3($episode);

        $this->assertFileExists($path);
        $this->assertSame(self::FAKE_MP3_CONTENT, file_get_contents($path));
    }

    /**
     * downloadMp3() creates the storage/podcasts directory if it does not exist.
     */
    public function test_download_mp3_creates_destination_directory_if_missing(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        // Ensure the directory does not exist before the call.
        $dir = storage_path('app/podcasts');

        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $entry) {
                if (is_file($entry)) {
                    unlink($entry);
                } elseif (is_dir($entry)) {
                    foreach (glob($entry . '/*') as $subEntry) {
                        if (is_file($subEntry)) {
                            unlink($subEntry);
                        }
                    }
                    rmdir($entry);
                }
            }
            rmdir($dir);
        }

        $this->assertDirectoryDoesNotExist($dir);

        $episode = $this->makeEpisode();
        $this->service()->downloadMp3($episode);

        $this->assertDirectoryExists($dir);
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  downloadMp3() — content validation                                    ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * downloadMp3() rejects an HTML login page from the /engine/ endpoint
     * and falls back to /api/.
     */
    public function test_download_mp3_rejects_html_from_engine_and_falls_back_to_api(): void
    {
        $loginPage = '<!DOCTYPE html><html><head><title>Auphonic Login</title></head><body>Login</body></html>';

        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response($loginPage, 200, ['Content-Type' => 'text/html']),
            '*auphonic.com/api/download/audio-result/*'    => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $episode = $this->makeEpisode();
        $path    = $this->service()->downloadMp3($episode);

        $this->assertFileExists($path);
        $this->assertSame(self::FAKE_MP3_CONTENT, file_get_contents($path));
    }

    /**
     * downloadMp3() throws when both endpoints return HTML login pages.
     */
    public function test_download_mp3_throws_when_both_endpoints_return_html(): void
    {
        $loginPage = '<!DOCTYPE html><html><head><title>Auphonic Login</title></head><body>Login</body></html>';

        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response($loginPage, 200, ['Content-Type' => 'text/html']),
            '*auphonic.com/api/download/audio-result/*'    => Http::response($loginPage, 200, ['Content-Type' => 'text/html']),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('non-audio content');

        $episode = $this->makeEpisode();
        $this->service()->downloadMp3($episode);
    }

    /**
     * downloadMp3() accepts application/octet-stream responses (some servers
     * use this for binary downloads instead of audio/mpeg).
     */
    public function test_download_mp3_accepts_octet_stream_content_type(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response(self::FAKE_MP3_CONTENT, 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $episode = $this->makeEpisode();
        $path    = $this->service()->downloadMp3($episode);

        $this->assertFileExists($path);
        $this->assertSame(self::FAKE_MP3_CONTENT, file_get_contents($path));
    }

    // ╔════════════════════════════════════════════════════════════════════════╗
    // ║  downloadMp3() — failure paths                                         ║
    // ╚════════════════════════════════════════════════════════════════════════╝

    /**
     * downloadMp3() throws a RuntimeException when both endpoints return
     * non-success HTTP responses.
     */
    public function test_download_mp3_throws_when_both_endpoints_return_non_success(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response('', 503),
            '*auphonic.com/api/download/audio-result/*'    => Http::response('', 404),
        ]);

        $this->expectException(\RuntimeException::class);

        $episode = $this->makeEpisode();
        $this->service()->downloadMp3($episode);
    }

    /**
     * downloadMp3() throws a RuntimeException when both endpoints throw
     * network-level exceptions.
     */
    public function test_download_mp3_throws_when_both_endpoints_throw(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => function () {
                throw new \RuntimeException('Connection refused.');
            },
            '*auphonic.com/api/download/audio-result/*' => function () {
                throw new \RuntimeException('Timeout.');
            },
        ]);

        $this->expectException(\RuntimeException::class);

        $episode = $this->makeEpisode();
        $this->service()->downloadMp3($episode);
    }

    /**
     * downloadMp3() does not write a file to disk when both endpoints fail.
     */
    public function test_download_mp3_does_not_write_file_when_both_endpoints_fail(): void
    {
        Http::fake([
            '*auphonic.com/engine/download/audio-result/*' => Http::response('', 503),
            '*auphonic.com/api/download/audio-result/*'    => Http::response('', 404),
        ]);

        $episode = $this->makeEpisode();

        try {
            $this->service()->downloadMp3($episode);
        } catch (\RuntimeException) {
            // Expected — we just want to assert the file was not written.
        }

        $this->assertFileDoesNotExist(storage_path('app/podcasts/episode-001.mp3'));
    }
}