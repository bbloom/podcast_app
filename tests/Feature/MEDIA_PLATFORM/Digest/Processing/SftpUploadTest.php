<?php

// tests/Feature/Processing/SftpUploadTest.php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToWriteFile;

uses(RefreshDatabase::class);

// ============================================================================
// SftpService::upload() — unit-level tests using a mocked Filesystem
//
// We cannot (and should not) make real SFTP connections in tests.
// The upload() method is tested by:
//   1. Checking the return value from a real call that hits a fake Flysystem
//   2. Verifying error handling for the known failure modes
//
// The Flysystem Filesystem is injected into the test via partial mocking of
// SftpService to bypass the real SFTP connection builder.
// ============================================================================

it('returns success array with path when upload succeeds', function () {
    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create([
        'type'      => 'sftp',
        'host'      => 'sftp.example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'password'  => 'secret',
        'path'      => '/var/www/digests',
    ]);

    // Partial mock: replace buildFilesystemFromDestination to return a mocked Filesystem
    $mockFilesystem = Mockery::mock(Filesystem::class);
    $mockFilesystem->shouldReceive('write')
        ->once()
        ->with('morning-tech-digest-2026-03-13.html', '<html>digest</html>')
        ->andReturnNull(); // write() returns void on success

    $service = Mockery::mock(SftpService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('buildFilesystemFromDestination')
        ->once()
        ->andReturn($mockFilesystem);

    $result = $service->upload($dest, 'morning-tech-digest-2026-03-13', '<html>digest</html>');

    expect($result['success'])->toBeTrue();
    expect($result['path'])->toBe('/var/www/digests/morning-tech-digest-2026-03-13.html');
});

it('returns failure array when Flysystem throws UnableToWriteFile', function () {
    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create([
        'type'      => 'sftp',
        'host'      => 'sftp.example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'password'  => 'secret',
        'path'      => '/var/www/digests',
    ]);

    $mockFilesystem = Mockery::mock(Filesystem::class);
    $mockFilesystem->shouldReceive('write')
        ->once()
        ->andThrow(UnableToWriteFile::atLocation('/var/www/digests/test', 'Permission denied'));

    $service = Mockery::mock(SftpService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('buildFilesystemFromDestination')
        ->once()
        ->andReturn($mockFilesystem);

    $result = $service->upload($dest, 'test', 'content');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Could not write file');
});

it('returns humanized failure array when a connection-level Throwable is thrown', function () {
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

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Connection refused');
});

// ============================================================================
// testWithPassword / testWithSshKey are already tested implicitly via the
// OutputDestination wizard tests. These spot-checks just confirm the
// humanizeError paths are reached for common connection error strings.
// ============================================================================

it('humanizes authentication failed error in test methods', function () {
    // We test the humanizeError branch indirectly through testWithPassword
    // by faking a connection that throws "Authentication failed"
    $service = new SftpService();

    // Use reflection to call the private humanizeError method directly
    $reflection = new \ReflectionClass($service);
    $method     = $reflection->getMethod('humanizeError');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'Authentication failed: bad credentials');

    expect($result['success'])->toBeFalse();
    expect($result['error_step'])->toBe(5);
    expect($result['message'])->toContain('Authentication failed');
});

it('humanizes connection refused error', function () {
    $service    = new SftpService();
    $reflection = new \ReflectionClass($service);
    $method     = $reflection->getMethod('humanizeError');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'Connection refused by server');

    expect($result['success'])->toBeFalse();
    expect($result['error_step'])->toBe(3);
});

it('humanizes connection timed out error', function () {
    $service    = new SftpService();
    $reflection = new \ReflectionClass($service);
    $method     = $reflection->getMethod('humanizeError');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'Connection timed out after 30s');

    expect($result['success'])->toBeFalse();
    expect($result['error_step'])->toBe(3);
});