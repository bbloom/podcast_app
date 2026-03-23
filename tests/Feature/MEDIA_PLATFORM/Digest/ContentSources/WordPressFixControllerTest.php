<?php

// tests/Feature/Lists/WordPressFixControllerTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// Valid WordPress wizard session for use across tests.
$validSession = fn () => [
    'od_wizard' => [
        'name'                   => 'My WP Site',
        'type'                   => 'wordpress',
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx xxxx xxxx xxxx',
        'wordpress_post_status'  => 'publish',
        'wordpress_category_ids' => null,
        'wordpress_tag_ids'      => null,
    ],
];

// ============================================================================
// Credentials fix
// ============================================================================

it('renders the credentials fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.wordpress.credentials'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-wordpress.credentials');
});

it('redirects to step1 from credentials fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.wordpress.credentials'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected credentials and redirects to wp3', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.credentials.submit'), [
            'wordpress_url'          => 'https://newsite.com',
            'wordpress_username'     => 'editor',
            'wordpress_app_password' => 'new-password',
        ])
        ->assertRedirect(route('output_destinations.create.wp3'));

    expect(session('od_wizard.wordpress_url'))->toBe('https://newsite.com');
    expect(session('od_wizard.wordpress_username'))->toBe('editor');
    expect(session('od_wizard.wp_test_passed'))->toBeFalse();
});

it('preserves existing app password when blank is submitted', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.credentials.submit'), [
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => '',
        ])
        ->assertRedirect(route('output_destinations.create.wp3'));

    expect(session('od_wizard.wordpress_app_password'))->toBe('xxxx xxxx xxxx xxxx');
});

it('rejects credentials fix with missing url', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.credentials.submit'), [
            'wordpress_url'      => '',
            'wordpress_username' => 'admin',
        ])
        ->assertSessionHasErrors('wordpress_url');
});

it('rejects credentials fix with invalid url', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.credentials.submit'), [
            'wordpress_url'      => 'not-a-url',
            'wordpress_username' => 'admin',
        ])
        ->assertSessionHasErrors('wordpress_url');
});

it('rejects credentials fix with missing username', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.credentials.submit'), [
            'wordpress_url'      => 'https://mysite.com',
            'wordpress_username' => '',
        ])
        ->assertSessionHasErrors('wordpress_username');
});

// ============================================================================
// Post settings fix
// ============================================================================

it('renders the post settings fix form when session is valid', function () use ($validSession) {
    $this->withSession($validSession())
        ->get(route('output_destinations.fix.wordpress.post_settings'))
        ->assertOk()
        ->assertViewIs('media_platform.digest.content_sources.output_destinations.fix-wordpress.post-settings');
});

it('redirects to step1 from post settings fix form when session is missing', function () {
    $this->get(route('output_destinations.fix.wordpress.post_settings'))
        ->assertRedirect(route('output_destinations.create.step1'));
});

it('saves corrected post settings and redirects to wp3', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.post_settings.submit'), [
            'wordpress_post_status'  => 'draft',
            'wordpress_category_ids' => '1,2,3',
            'wordpress_tag_ids'      => '5',
        ])
        ->assertRedirect(route('output_destinations.create.wp3'));

    expect(session('od_wizard.wordpress_post_status'))->toBe('draft');
    expect(session('od_wizard.wordpress_category_ids'))->toBe('1,2,3');
    expect(session('od_wizard.wp_test_passed'))->toBeFalse();
});

it('rejects post settings fix with invalid post status', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.post_settings.submit'), [
            'wordpress_post_status' => 'invalid',
        ])
        ->assertSessionHasErrors('wordpress_post_status');
});

it('accepts blank category and tag ids on post settings fix', function () use ($validSession) {
    $this->withSession($validSession())
        ->post(route('output_destinations.fix.wordpress.post_settings.submit'), [
            'wordpress_post_status'  => 'publish',
            'wordpress_category_ids' => '',
            'wordpress_tag_ids'      => '',
        ])
        ->assertRedirect(route('output_destinations.create.wp3'));
});