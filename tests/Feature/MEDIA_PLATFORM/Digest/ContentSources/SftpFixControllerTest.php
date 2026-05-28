<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/SftpFixControllerTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SftpFixControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function validSession(): array
    {
        return [
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
    }

    // =========================================================================
    // Host fix
    // =========================================================================

    #[Test]
    public function renders_the_host_fix_form_when_session_is_valid(): void
    {
        $this->withSession($this->validSession())
             ->get(route('output_destinations.fix.sftp.host'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.host');
    }

    #[Test]
    public function redirects_to_step1_from_host_fix_form_when_session_is_missing(): void
    {
        $this->get(route('output_destinations.fix.sftp.host'))
             ->assertRedirect(route('output_destinations.create.step1'));
    }

    #[Test]
    public function saves_corrected_host_and_port_and_redirects_to_step7(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.host.submit'), [
                 'host' => 'newsftp.example.com',
                 'port' => 2222,
             ])
             ->assertRedirect(route('output_destinations.create.step7'));

        $this->assertSame('newsftp.example.com', session('od_wizard.host'));
        $this->assertSame(2222, session('od_wizard.port'));
        $this->assertFalse(session('od_wizard.test_passed'));
    }

    #[Test]
    public function rejects_host_fix_with_missing_host(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.host.submit'), [
                 'host' => '',
                 'port' => 22,
             ])
             ->assertSessionHasErrors('host');
    }

    #[Test]
    public function rejects_host_fix_with_invalid_port(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.host.submit'), [
                 'host' => 'sftp.example.com',
                 'port' => 99999,
             ])
             ->assertSessionHasErrors('port');
    }

    // =========================================================================
    // Username fix
    // =========================================================================

    #[Test]
    public function renders_the_username_fix_form_when_session_is_valid(): void
    {
        $this->withSession($this->validSession())
             ->get(route('output_destinations.fix.sftp.username'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.username');
    }

    #[Test]
    public function redirects_to_step1_from_username_fix_form_when_session_is_missing(): void
    {
        $this->get(route('output_destinations.fix.sftp.username'))
             ->assertRedirect(route('output_destinations.create.step1'));
    }

    #[Test]
    public function saves_corrected_username_and_redirects_to_step7(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.username.submit'), [
                 'username' => 'newuser',
             ])
             ->assertRedirect(route('output_destinations.create.step7'));

        $this->assertSame('newuser', session('od_wizard.username'));
        $this->assertFalse(session('od_wizard.test_passed'));
    }

    #[Test]
    public function rejects_username_fix_with_missing_username(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.username.submit'), [
                 'username' => '',
             ])
             ->assertSessionHasErrors('username');
    }

    // =========================================================================
    // Auth fix
    // =========================================================================

    #[Test]
    public function renders_the_auth_fix_form_when_session_is_valid(): void
    {
        $this->withSession($this->validSession())
             ->get(route('output_destinations.fix.sftp.auth'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.auth');
    }

    #[Test]
    public function redirects_to_step1_from_auth_fix_form_when_session_is_missing(): void
    {
        $this->get(route('output_destinations.fix.sftp.auth'))
             ->assertRedirect(route('output_destinations.create.step1'));
    }

    #[Test]
    public function saves_corrected_password_auth_and_redirects_to_step7(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.auth.submit'), [
                 'auth_type' => 'password',
                 'password'  => 'newpassword',
             ])
             ->assertRedirect(route('output_destinations.create.step7'));

        $this->assertSame('password', session('od_wizard.auth_type'));
        $this->assertSame('newpassword', session('od_wizard.password'));
        $this->assertFalse(session('od_wizard.test_passed'));
    }

    #[Test]
    public function preserves_existing_password_when_blank_is_submitted_on_auth_fix(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.auth.submit'), [
                 'auth_type' => 'password',
                 'password'  => '',
             ])
             ->assertRedirect(route('output_destinations.create.step7'));

        $this->assertSame('secret', session('od_wizard.password'));
    }

    #[Test]
    public function rejects_auth_fix_with_invalid_auth_type(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.auth.submit'), [
                 'auth_type' => 'invalid',
             ])
             ->assertSessionHasErrors('auth_type');
    }

    // =========================================================================
    // Path fix
    // =========================================================================

    #[Test]
    public function renders_the_path_fix_form_when_session_is_valid(): void
    {
        $this->withSession($this->validSession())
             ->get(route('output_destinations.fix.sftp.path'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-sftp.path');
    }

    #[Test]
    public function redirects_to_step1_from_path_fix_form_when_session_is_missing(): void
    {
        $this->get(route('output_destinations.fix.sftp.path'))
             ->assertRedirect(route('output_destinations.create.step1'));
    }

    #[Test]
    public function saves_corrected_path_and_redirects_to_step7(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.path.submit'), [
                 'path'     => '/new/path',
                 'base_url' => 'https://example.com/new',
             ])
             ->assertRedirect(route('output_destinations.create.step7'));

        $this->assertSame('/new/path', session('od_wizard.path'));
        $this->assertSame('https://example.com/new', session('od_wizard.base_url'));
        $this->assertFalse(session('od_wizard.test_passed'));
    }

    #[Test]
    public function rejects_path_fix_with_missing_path(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.path.submit'), [
                 'path' => '',
             ])
             ->assertSessionHasErrors('path');
    }

    #[Test]
    public function rejects_path_fix_with_invalid_base_url(): void
    {
        $this->withSession($this->validSession())
             ->post(route('output_destinations.fix.sftp.path.submit'), [
                 'path'     => '/var/www',
                 'base_url' => 'not-a-url',
             ])
             ->assertSessionHasErrors('base_url');
    }
}