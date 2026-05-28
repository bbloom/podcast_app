<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/SftpUploadTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SftpUploadTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeDest(User $user): OutputDestination
    {
        return OutputDestination::factory()->forUser($user)->create([
            'type'      => 'sftp',
            'host'      => 'sftp.example.com',
            'port'      => 22,
            'username'  => 'deploy',
            'auth_type' => 'password',
            'password'  => 'secret',
            'path'      => '/var/www/digests',
        ]);
    }

    private function humanizeError(string $message): array
    {
        $svc        = new SftpService();
        $reflection = new \ReflectionClass($svc);
        $method     = $reflection->getMethod('humanizeError');
        $method->setAccessible(true);
        return $method->invoke($svc, $message);
    }

    // =========================================================================
    // SftpService::upload()
    // =========================================================================

    #[Test]
    public function returns_success_array_with_path_when_upload_succeeds(): void
    {
        $user = User::factory()->create();
        $dest = $this->makeDest($user);

        $mockFilesystem = Mockery::mock(Filesystem::class);
        $mockFilesystem->shouldReceive('write')
            ->once()
            ->with('morning-tech-digest-2026-03-13.html', '<html>digest</html>')
            ->andReturnNull();

        $service = Mockery::mock(SftpService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('buildFilesystemFromDestination')->once()->andReturn($mockFilesystem);

        $result = $service->upload($dest, 'morning-tech-digest-2026-03-13', '<html>digest</html>');

        $this->assertTrue($result['success']);
        $this->assertSame('/var/www/digests/morning-tech-digest-2026-03-13.html', $result['path']);
    }

    #[Test]
    public function returns_failure_array_when_Flysystem_throws_UnableToWriteFile(): void
    {
        $user = User::factory()->create();
        $dest = $this->makeDest($user);

        $mockFilesystem = Mockery::mock(Filesystem::class);
        $mockFilesystem->shouldReceive('write')
            ->once()
            ->andThrow(UnableToWriteFile::atLocation('/var/www/digests/test', 'Permission denied'));

        $service = Mockery::mock(SftpService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('buildFilesystemFromDestination')->once()->andReturn($mockFilesystem);

        $result = $service->upload($dest, 'test', 'content');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Could not write file', $result['message']);
    }

    #[Test]
    public function returns_humanized_failure_array_when_a_connection_level_Throwable_is_thrown(): void
    {
        $user = User::factory()->create();
        $dest = OutputDestination::factory()->forUser($user)->create([
            'type'      => 'sftp',
            'host'      => 'sftp.example.com',
            'port'      => 22,
            'username'  => 'deploy',
            'auth_type' => 'password',
            'password'  => 'secret',
            'path'      => '/digests',
        ]);

        $service = Mockery::mock(SftpService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('buildFilesystemFromDestination')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $result = $service->upload($dest, 'test', 'content');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection refused', $result['message']);
    }

    // =========================================================================
    // humanizeError — reflection spot-checks
    // =========================================================================

    #[Test]
    public function humanizes_authentication_failed_error(): void
    {
        $result = $this->humanizeError('Authentication failed: bad credentials');

        $this->assertFalse($result['success']);
        $this->assertSame(5, $result['error_step']);
        $this->assertStringContainsString('Authentication failed', $result['message']);
    }

    #[Test]
    public function humanizes_connection_refused_error(): void
    {
        $result = $this->humanizeError('Connection refused by server');

        $this->assertFalse($result['success']);
        $this->assertSame(3, $result['error_step']);
    }

    #[Test]
    public function humanizes_connection_timed_out_error(): void
    {
        $result = $this->humanizeError('Connection timed out after 30s');

        $this->assertFalse($result['success']);
        $this->assertSame(3, $result['error_step']);
    }
}