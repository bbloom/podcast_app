<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/SftpFixControllerTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// Valid SFTP wizard session for use across tests.
$validSession = fn () => [
    'od_wizard' => [
        'name'        => 'My SFTP Server',
        'type'        => 'sftp',
        'host'        => 'sftp.example.com',
        'port'        => 22,
        'username'    => 'deploy',
        'auth_type'   => 'password',
        'password'    => 'secret',
        'path'        => '/var/www/digests',
        'test_passed' => false,
    ],
];

// ============================================================================
// Host fix
// ============================================================================

it('renders the host fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.sftp.host'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.host');
});

it('redirects to step1 from host fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.sftp.host'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected host and port and redirects to step7', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.host.submit'), [
            'host' => 'newsftp.example.com',
            'port' => 2222,
        ])
        ->assertRedirect(route('output_destinations.create.step7'));

    expect(session('od_wizard.host'))->toBe('newsftp.example.com');
    expect(session('od_wizard.port'))->toBe(2222);
    expect(session('od_wizard.test_passed'))->toBeFalse();
});

it('rejects host fix with missing host', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.host.submit'), [
            'host' => '',
            'port' => 22,
        ])
        ->assertSessionHasErrors('host');
});

it('rejects host fix with invalid port', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.host.submit'), [
            'host' => 'sftp.example.com',
            'port' => 99999,
        ])
        ->assertSessionHasErrors('port');
});

// ============================================================================
// Username fix
// ============================================================================

it('renders the username fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.sftp.username'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.username');
});

it('redirects to step1 from username fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.sftp.username'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected username and redirects to step7', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.username.submit'), [
            'username' => 'newuser',
        ])
        ->assertRedirect(route('output_destinations.create.step7'));

    expect(session('od_wizard.username'))->toBe('newuser');
    expect(session('od_wizard.test_passed'))->toBeFalse();
});

it('rejects username fix with missing username', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.username.submit'), [
            'username' => '',
        ])
        ->assertSessionHasErrors('username');
});

// ============================================================================
// Auth fix
// ============================================================================

it('renders the auth fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.sftp.auth'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.auth');
});

it('redirects to step1 from auth fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.sftp.auth'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected password auth and redirects to step7', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.auth.submit'), [
            'auth_type' => 'password',
            'password'  => 'newpassword',
        ])
        ->assertRedirect(route('output_destinations.create.step7'));

    expect(session('od_wizard.auth_type'))->toBe('password');
    expect(session('od_wizard.password'))->toBe('newpassword');
    expect(session('od_wizard.test_passed'))->toBeFalse();
});

it('preserves existing password when blank is submitted on auth fix', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.auth.submit'), [
            'auth_type' => 'password',
            'password'  => '',
        ])
        ->assertRedirect(route('output_destinations.create.step7'));

    expect(session('od_wizard.password'))->toBe('secret');
});

it('rejects auth fix with invalid auth_type', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.auth.submit'), [
            'auth_type' => 'invalid',
        ])
        ->assertSessionHasErrors('auth_type');
});

// ============================================================================
// Path fix
// ============================================================================

it('renders the path fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.sftp.path'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.path');
});

it('redirects to step1 from path fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.sftp.path'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected path and redirects to step7', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.path.submit'), [
            'path'     => '/new/path',
            'base_url' => 'https://example.com/new',
        ])
        ->assertRedirect(route('output_destinations.create.step7'));

    expect(session('od_wizard.path'))->toBe('/new/path');
    expect(session('od_wizard.base_url'))->toBe('https://example.com/new');
    expect(session('od_wizard.test_passed'))->toBeFalse();
});

it('rejects path fix with missing path', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.path.submit'), [
            'path' => '',
        ])
        ->assertSessionHasErrors('path');
});

it('rejects path fix with invalid base_url', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.sftp.path.submit'), [
            'path'     => '/var/www',
            'base_url' => 'not-a-url',
        ])
        ->assertSessionHasErrors('base_url');
});