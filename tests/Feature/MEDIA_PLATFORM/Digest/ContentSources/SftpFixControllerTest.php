<?php

// tests/Feature/Lists/WordPressWizardTest.php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\WordPressService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ============================================================================
// Step 1 (Name) → Step 2 (Type = wordpress) → WP1 → WP2 → WP3 → Save
// ============================================================================

it('walks the full WordPress wizard flow and creates an OutputDestination', function () {
    $this->post(route('output_destinations.create.step1.submit'), [
        'name' => 'My WordPress Site',
    ])->assertRedirect(route('output_destinations.create.step2'));

    $this->post(route('output_destinations.create.step2.submit'), [
        'type' => 'wordpress',
    ])->assertRedirect(route('output_destinations.create.wp1'));

    $this->post(route('output_destinations.create.wp1.submit'), [
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx xxxx xxxx xxxx',
    ])->assertRedirect(route('output_destinations.create.wp2'));

    $this->post(route('output_destinations.create.wp2.submit'), [
        'wordpress_post_status'  => 'publish',
        'wordpress_category_ids' => '3,7',
        'wordpress_tag_ids'      => '12',
    ])->assertRedirect(route('output_destinations.create.wp3'));

    // Simulate passing the connection test.
    session()->put('od_wizard.wp_test_passed', true);

    $this->post(route('output_destinations.create.wp3.submit'))
        ->assertRedirect(route('output_destinations.create.step9'));

    $this->assertDatabaseHas('output_destinations', [
        'user_id'                => $this->user->id,
        'name'                   => 'My WordPress Site',
        'type'                   => 'wordpress',
        'wordpress_post_status'  => 'publish',
        'wordpress_category_ids' => '3,7',
        'wordpress_tag_ids'      => '12',
    ]);
});

// ============================================================================
// WP1 validation
// ============================================================================

it('rejects wp1 if wordpress_url is missing', function () {
    $this->withSession(['od_wizard' => ['name' => 'Test', 'type' => 'wordpress']]);

    $this->post(route('output_destinations.create.wp1.submit'), [
        'wordpress_url'          => '',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx',
    ])->assertSessionHasErrors('wordpress_url');
});

it('rejects wp1 if wordpress_url is not a valid URL', function () {
    $this->withSession(['od_wizard' => ['name' => 'Test', 'type' => 'wordpress']]);

    $this->post(route('output_destinations.create.wp1.submit'), [
        'wordpress_url'          => 'not-a-url',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx',
    ])->assertSessionHasErrors('wordpress_url');
});

it('rejects wp1 if username is missing', function () {
    $this->withSession(['od_wizard' => ['name' => 'Test', 'type' => 'wordpress']]);

    $this->post(route('output_destinations.create.wp1.submit'), [
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => '',
        'wordpress_app_password' => 'xxxx',
    ])->assertSessionHasErrors('wordpress_username');
});

it('rejects wp1 if app password is missing', function () {
    $this->withSession(['od_wizard' => ['name' => 'Test', 'type' => 'wordpress']]);

    $this->post(route('output_destinations.create.wp1.submit'), [
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => '',
    ])->assertSessionHasErrors('wordpress_app_password');
});

// ============================================================================
// WP2 validation
// ============================================================================

it('rejects wp2 if post status is invalid', function () {
    $this->withSession([
        'od_wizard' => [
            'name'                   => 'Test',
            'type'                   => 'wordpress',
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'xxxx',
        ],
    ]);

    $this->post(route('output_destinations.create.wp2.submit'), [
        'wordpress_post_status' => 'invalid_status',
    ])->assertSessionHasErrors('wordpress_post_status');
});

it('accepts blank category and tag IDs on wp2', function () {
    $this->withSession([
        'od_wizard' => [
            'name'                   => 'Test',
            'type'                   => 'wordpress',
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'xxxx',
        ],
    ]);

    $this->post(route('output_destinations.create.wp2.submit'), [
        'wordpress_post_status'  => 'draft',
        'wordpress_category_ids' => '',
        'wordpress_tag_ids'      => '',
    ])->assertRedirect(route('output_destinations.create.wp3'));
});

// ============================================================================
// WP3 — cannot save without passing the test
// ============================================================================

it('blocks wp3 save if connection test has not been passed', function () {
    $this->withSession([
        'od_wizard' => [
            'name'                   => 'Test',
            'type'                   => 'wordpress',
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'xxxx',
            'wordpress_post_status'  => 'publish',
        ],
    ]);

    $this->post(route('output_destinations.create.wp3.submit'))
        ->assertSessionHasErrors('test');

    $this->assertDatabaseMissing('output_destinations', ['type' => 'wordpress']);
});

// ============================================================================
// AJAX: testWordPressConnection
// ============================================================================

it('testWordPressConnection returns success json when credentials are valid', function () {
    $this->withSession([
        'od_wizard' => [
            'name'                   => 'Test',
            'type'                   => 'wordpress',
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'xxxx',
        ],
    ]);

    $this->mock(WordPressService::class, function ($mock) {
        $mock->shouldReceive('testConnection')
            ->once()
            ->andReturn(['success' => true]);
    });

    $this->postJson(route('output_destinations.wizard.test_wordpress'))
        ->assertOk()
        ->assertJson(['success' => true])
        ->assertSessionHas('od_wizard.wp_test_passed', true);
});

it('testWordPressConnection returns failure json when credentials are wrong', function () {
    $this->withSession([
        'od_wizard' => [
            'name'                   => 'Test',
            'type'                   => 'wordpress',
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'wrong',
        ],
    ]);

    $this->mock(WordPressService::class, function ($mock) {
        $mock->shouldReceive('testConnection')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Authentication failed.']);
    });

    $this->postJson(route('output_destinations.wizard.test_wordpress'))
        ->assertOk()
        ->assertJson(['success' => false])
        ->assertSessionHas('od_wizard.wp_test_passed', false);
});

// ============================================================================
// Step 2 type branching
// ============================================================================

it('redirects to step3 (SFTP) when type is sftp at step2', function () {
    $this->withSession(['od_wizard' => ['name' => 'My SFTP']]);

    $this->post(route('output_destinations.create.step2.submit'), [
        'type' => 'sftp',
    ])->assertRedirect(route('output_destinations.create.step3'));
});

it('redirects to wp1 when type is wordpress at step2', function () {
    $this->withSession(['od_wizard' => ['name' => 'My WP']]);

    $this->post(route('output_destinations.create.step2.submit'), [
        'type' => 'wordpress',
    ])->assertRedirect(route('output_destinations.create.wp1'));
});

it('rejects an invalid type at step2', function () {
    $this->withSession(['od_wizard' => ['name' => 'Test']]);

    $this->post(route('output_destinations.create.step2.submit'), [
        'type' => 'ftp',
    ])->assertSessionHasErrors('type');
});

// ============================================================================
// OutputDestination factory (ensure wordpress type works)
// ============================================================================

it('can create a wordpress OutputDestination directly', function () {
    $dest = OutputDestination::factory()
        ->forUser($this->user)
        ->wordpress()
        ->create([
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'encrypted-password',
            'wordpress_post_status'  => 'draft',
        ]);

    $this->assertDatabaseHas('output_destinations', [
        'id'   => $dest->id,
        'type' => 'wordpress',
    ]);

    $raw = \Illuminate\Support\Facades\DB::table('output_destinations')
        ->where('id', $dest->id)
        ->value('wordpress_app_password');

    expect($raw)->not->toBe('encrypted-password');
    expect($dest->wordpress_app_password)->toBe('encrypted-password');
});