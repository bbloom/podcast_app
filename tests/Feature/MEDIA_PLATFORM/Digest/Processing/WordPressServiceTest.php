<?php

// tests/Feature/Processing/WordPressServiceTest.php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\WordPressService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// ============================================================================
// Helpers
// ============================================================================

/**
 * Create a WordPress OutputDestination with default test values.
 */
function makeWordPressDest(User $user, array $overrides = []): OutputDestination
{
    // Use the ->wordpress() state so SFTP columns are correctly null,
    // then apply any test-specific overrides on top.
    return OutputDestination::factory()
        ->forUser($user)
        ->wordpress()
        ->create(array_merge([
            'wordpress_url'          => 'https://mysite.com',
            'wordpress_username'     => 'admin',
            'wordpress_app_password' => 'test app password',
            'wordpress_post_status'  => 'publish',
            'wordpress_category_ids' => null,
            'wordpress_tag_ids'      => null,
        ], $overrides));
}

// ============================================================================
// createPost() — success
// ============================================================================

it('creates a post and returns success with post_id and url', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response([
            'id'   => 99,
            'link' => 'https://mysite.com/test-digest-2026-03-13',
        ], 201),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user);
    $service = new WordPressService();

    $result = $service->createPost(
        dest:        $dest,
        title:       'Morning Tech Digest — Fri, Mar 13 2026',
        slug:        'morning-tech-digest-2026-03-13',
        htmlContent: '<p>Content here.</p>',
        excerpt:     '5 items from 2 sources',
        date:        Carbon::parse('2026-03-13 08:00:00'),
    );

    expect($result['success'])->toBeTrue();
    expect($result['post_id'])->toBe(99);
    expect($result['url'])->toBe('https://mysite.com/test-digest-2026-03-13');
});

it('sends correct fields in the API payload', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response(['id' => 1, 'link' => 'https://mysite.com/?p=1'], 201),
    ]);

    $user = User::factory()->create();
    $dest = makeWordPressDest($user, [
        'wordpress_post_status'  => 'draft',
        'wordpress_category_ids' => '3,7',
        'wordpress_tag_ids'      => '12',
    ]);

    $service = new WordPressService();
    $service->createPost(
        dest:        $dest,
        title:       'Tech Digest',
        slug:        'tech-digest-2026-03-13',
        htmlContent: '<p>Body</p>',
        excerpt:     '2 items from 1 source',
        date:        Carbon::parse('2026-03-13'),
    );

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $body['title'] === 'Tech Digest'
            && $body['slug']  === 'tech-digest-2026-03-13'
            && $body['status'] === 'draft'
            && $body['excerpt'] === '2 items from 1 source'
            && $body['categories'] === [3, 7]
            && $body['tags'] === [12]
            && isset($body['date'])
            && isset($body['content']);
    });
});

it('sends empty arrays for categories and tags when none are configured', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response(['id' => 1, 'link' => ''], 201),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user); // no category/tag IDs
    $service = new WordPressService();

    $service->createPost(
        dest:        $dest,
        title:       'Digest',
        slug:        'digest-2026-03-13',
        htmlContent: '<p>.</p>',
        excerpt:     '1 item from 1 source',
        date:        Carbon::now(),
    );

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $body['categories'] === []
            && $body['tags'] === [];
    });
});

it('uses Basic Auth with the correct credentials', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response(['id' => 1, 'link' => ''], 201),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user, [
        'wordpress_username'     => 'myuser',
        'wordpress_app_password' => 'secret password',
    ]);
    $service = new WordPressService();

    $service->createPost(
        dest:        $dest,
        title:       'Digest',
        slug:        'digest-2026-03-13',
        htmlContent: '<p>.</p>',
        excerpt:     '1 item from 1 source',
        date:        Carbon::now(),
    );

    Http::assertSent(function ($request) {
        // Basic Auth encodes "username:password" in base64
        $expected = base64_encode('myuser:secret password');
        return $request->header('Authorization')[0] === "Basic {$expected}";
    });
});

// ============================================================================
// createPost() — API error responses
// ============================================================================

it('returns failure with human-readable message on 401', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response(
            ['code' => 'invalid_username', 'message' => 'Unknown username.'],
            401
        ),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user);
    $service = new WordPressService();

    $result = $service->createPost(
        dest: $dest, title: 'D', slug: 's', htmlContent: '<p>.</p>',
        excerpt: 'x', date: Carbon::now()
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Authentication failed');
});

it('returns failure with human-readable message on 403', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response([], 403),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user);
    $service = new WordPressService();

    $result = $service->createPost(
        dest: $dest, title: 'D', slug: 's', htmlContent: '<p>.</p>',
        excerpt: 'x', date: Carbon::now()
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('permission');
});

it('returns failure with human-readable message on 404', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/posts' => Http::response([], 404),
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user);
    $service = new WordPressService();

    $result = $service->createPost(
        dest: $dest, title: 'D', slug: 's', htmlContent: '<p>.</p>',
        excerpt: 'x', date: Carbon::now()
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('REST API not found');
});

it('returns failure on connection exception', function () {
    Http::fake([
        'https://mysite.com/*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    $user    = User::factory()->create();
    $dest    = makeWordPressDest($user);
    $service = new WordPressService();

    $result = $service->createPost(
        dest: $dest, title: 'D', slug: 's', htmlContent: '<p>.</p>',
        excerpt: 'x', date: Carbon::now()
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('connect');
});

// ============================================================================
// testConnection()
// ============================================================================

it('returns success when credentials are valid', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/users/me' => Http::response(
            ['id' => 1, 'name' => 'admin'],
            200
        ),
    ]);

    $service = new WordPressService();
    $result  = $service->testConnection('https://mysite.com', 'admin', 'app-password');

    expect($result['success'])->toBeTrue();
});

it('returns failure with authentication message on 401', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/users/me' => Http::response([], 401),
    ]);

    $service = new WordPressService();
    $result  = $service->testConnection('https://mysite.com', 'admin', 'wrong');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Authentication failed');
});

it('returns failure with REST API message on 404', function () {
    Http::fake([
        'https://mysite.com/wp-json/wp/v2/users/me' => Http::response([], 404),
    ]);

    $service = new WordPressService();
    $result  = $service->testConnection('https://mysite.com', 'admin', 'pw');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('not found');
});

// ============================================================================
// OutputDestination helpers
// ============================================================================

it('parses comma-separated category ids into an integer array', function () {
    $user = User::factory()->create();
    $dest = makeWordPressDest($user, ['wordpress_category_ids' => '3, 7, 12']);

    expect($dest->wordpressCategoryIdsArray())->toBe([3, 7, 12]);
});

it('returns empty array for blank category ids', function () {
    $user = User::factory()->create();
    $dest = makeWordPressDest($user, ['wordpress_category_ids' => null]);

    expect($dest->wordpressCategoryIdsArray())->toBe([]);
});

it('parses comma-separated tag ids into an integer array', function () {
    $user = User::factory()->create();
    $dest = makeWordPressDest($user, ['wordpress_tag_ids' => '10,20']);

    expect($dest->wordpressTagIdsArray())->toBe([10, 20]);
});