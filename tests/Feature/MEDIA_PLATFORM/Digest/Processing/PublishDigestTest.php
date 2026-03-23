<?php

// tests/Feature/Processing/PublishDigestTest.php

use MediaPlatform\Enums\OutputType;
use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\WordPressService;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use App\Models\User;
use MediaPlatform\Digest\Publishing\Notifications\DigestEmptyNotification;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Processing\Jobs\PublishDigest;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ============================================================================
// Helpers
// ============================================================================

/**
 * Create a list with one pending summary ready to be published.
 */
function makePublishableList(User $user, array $listOverrides = []): ListModel
{
    $feedId = DB::table('text_based_rss_feeds')->insertGetId([
        'user_id'    => $user->id,
        'title'      => 'Test Feed',
        'rss_url'    => 'https://example.com/feed.xml',
        'enabled'    => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $list = ListModel::factory()->forUser($user)->create(array_merge([
        'output_type' => OutputType::Email,
    ], $listOverrides));

    $listSourceId = DB::table('list_sources')->insertGetId([
        'list_id'         => $list->id,
        'sourceable_id'   => $feedId,
        'sourceable_type' => 'text_based_rss_feed',
        'enabled'         => true,
        'suspended'       => false,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    DB::table('summaries')->insert([
        'user_id'            => $user->id,
        'list_source_id'     => $listSourceId,
        'source_url'         => 'https://example.com/article-1',
        'source_title'       => 'Test Article',
        'processing_mode'    => 'description',
        'summary_html'       => '<p>Test summary.</p>',
        'is_relevant'        => true,
        'included_in_digest' => false,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    return $list->fresh();
}

// ============================================================================
// Email output type
// ============================================================================

it('sends a digest email when output_type is email', function () {
    Mail::fake();
    Notification::fake();

    $user = User::factory()->create();
    $list = makePublishableList($user, ['output_type' => OutputType::Email]);

    dispatch_sync(new PublishDigest($list->id));

    Mail::assertSent(DigestMailable::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('marks summaries as included after email delivery', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = makePublishableList($user, ['output_type' => OutputType::Email]);

    dispatch_sync(new PublishDigest($list->id));

    $included = DB::table('summaries')
        ->where('included_in_digest', true)
        ->count();

    expect($included)->toBe(1);
});

it('updates last_run_at after email delivery', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = makePublishableList($user, ['output_type' => OutputType::Email]);

    dispatch_sync(new PublishDigest($list->id));

    $list->refresh();
    expect($list->last_run_at)->not->toBeNull();
});

// ============================================================================
// Empty digest — no pending summaries
// ============================================================================

it('sends DigestEmptyNotification when there are no pending summaries', function () {
    Notification::fake();
    Mail::fake();

    $user = User::factory()->create();

    // Create a list with NO summaries
    $list = ListModel::factory()->forUser($user)->create([
        'output_type' => OutputType::Email,
    ]);

    dispatch_sync(new PublishDigest($list->id));

    Notification::assertSentTo($user, DigestEmptyNotification::class);
    Mail::assertNothingSent(); // no digest email, just the notification
});

it('still updates last_run_at when digest is empty', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    dispatch_sync(new PublishDigest($list->id));

    $list->refresh();
    expect($list->last_run_at)->not->toBeNull();
});

it('does not mark any summaries when digest is empty', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    dispatch_sync(new PublishDigest($list->id));

    $count = DB::table('summaries')->where('included_in_digest', true)->count();
    expect($count)->toBe(0);
});

// ============================================================================
// Webpage output type (SFTP)
// ============================================================================

it('uploads a file via SFTP when output_type is webpage', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create([
        'type'     => 'sftp',
        'path'     => '/digests',
        'base_url' => 'https://mysite.com/digests',
    ]);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
        'notify_by_email'       => false,
    ]);

    // Mock SftpService::upload to return success without a real SFTP connection
    $this->mock(SftpService::class, function ($mock) {
        $mock->shouldReceive('upload')
            ->once()
            ->andReturn(['success' => true, 'path' => '/digests/test-feed-digest-2026-03-13']);
    });

    dispatch_sync(new PublishDigest($list->id));

    // Summaries should be marked as included
    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(1);
});

it('sends DigestReadyNotification after SFTP upload when notify_by_email is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create([
        'type'     => 'sftp',
        'base_url' => 'https://mysite.com/digests',
    ]);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
        'notify_by_email'       => true,
    ]);

    $this->mock(SftpService::class, function ($mock) {
        $mock->shouldReceive('upload')
            ->once()
            ->andReturn(['success' => true, 'path' => '/digests/test']);
    });

    dispatch_sync(new PublishDigest($list->id));

    Notification::assertSentTo($user, DigestReadyNotification::class);
});

it('does not send DigestReadyNotification when notify_by_email is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create(['type' => 'sftp']);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
        'notify_by_email'       => false,
    ]);

    $this->mock(SftpService::class, function ($mock) {
        $mock->shouldReceive('upload')->once()->andReturn(['success' => true, 'path' => '/digests/test']);
    });

    dispatch_sync(new PublishDigest($list->id));

    Notification::assertNotSentTo($user, DigestReadyNotification::class);
});

it('does not mark summaries as included if SFTP upload fails', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create(['type' => 'sftp']);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
    ]);

    $this->mock(SftpService::class, function ($mock) {
        $mock->shouldReceive('upload')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Connection refused.']);
    });

    dispatch_sync(new PublishDigest($list->id));

    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(0);
});

it('raises an AdminAlert when SFTP upload fails', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create(['type' => 'sftp']);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
    ]);

    $this->mock(SftpService::class, function ($mock) {
        $mock->shouldReceive('upload')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Connection refused.']);
    });

    dispatch_sync(new PublishDigest($list->id));

    $this->assertDatabaseHas('admin_alerts', [
        'category'    => 'sftp',
        'is_resolved' => false,
    ]);
});

// ============================================================================
// WordPress output type
// ============================================================================

it('calls WordPressService::createPost when output_type is wordpress', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->wordpress()->create([
        'wordpress_url'         => 'https://mysite.com',
        'wordpress_username'    => 'admin',
        'wordpress_app_password' => 'xxxx xxxx xxxx',
        'wordpress_post_status' => 'publish',
    ]);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Wordpress,
        'output_destination_id' => $dest->id,
    ]);

    $this->mock(WordPressService::class, function ($mock) {
        $mock->shouldReceive('createPost')
            ->once()
            ->andReturn(['success' => true, 'post_id' => 42, 'url' => 'https://mysite.com/?p=42']);
    });

    dispatch_sync(new PublishDigest($list->id));

    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(1);
});

it('does not mark summaries as included if WordPress publish fails', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->wordpress()->create([
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx xxxx xxxx',
    ]);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Wordpress,
        'output_destination_id' => $dest->id,
    ]);

    $this->mock(WordPressService::class, function ($mock) {
        $mock->shouldReceive('createPost')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Authentication failed.']);
    });

    dispatch_sync(new PublishDigest($list->id));

    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(0);
});

it('raises an AdminAlert when WordPress publish fails', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->wordpress()->create([
        'wordpress_url'          => 'https://mysite.com',
        'wordpress_username'     => 'admin',
        'wordpress_app_password' => 'xxxx xxxx xxxx',
    ]);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Wordpress,
        'output_destination_id' => $dest->id,
    ]);

    $this->mock(WordPressService::class, function ($mock) {
        $mock->shouldReceive('createPost')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Authentication failed.']);
    });

    dispatch_sync(new PublishDigest($list->id));

    $this->assertDatabaseHas('admin_alerts', [
        'category'    => 'infrastructure',
        'is_resolved' => false,
    ]);
});

// ============================================================================
// ProcessingGate
// ============================================================================

it('aborts publish when ProcessingGate blocks and does not mark summaries', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = OutputDestination::factory()->forUser($user)->create(['type' => 'sftp']);
    $list = makePublishableList($user, [
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => $dest->id,
    ]);

    // Insert a Tier 3 SFTP alert to block publishing
    AdminAlert::create([
        'tier'        => 3,
        'category'    => 'sftp',
        'title'       => 'SFTP blocked',
        'message'     => 'Test block',
        'is_resolved' => false,
    ]);

    $this->mock(SftpService::class, function ($mock) {
        // upload should NEVER be called when the gate is closed
        $mock->shouldNotReceive('upload');
    });

    dispatch_sync(new PublishDigest($list->id));

    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(0);
});

it('does not check the gate for email output type', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = makePublishableList($user, ['output_type' => OutputType::Email]);

    // Even with an infra-level Tier 3 alert, email should go through
    AdminAlert::create([
        'tier'        => 3,
        'category'    => 'infrastructure',
        'title'       => 'Infra blocked',
        'message'     => 'Test block',
        'is_resolved' => false,
    ]);

    dispatch_sync(new PublishDigest($list->id));

    Mail::assertSent(DigestMailable::class);
});

// ============================================================================
// Edge cases
// ============================================================================

it('does nothing gracefully when the list does not exist', function () {
    Mail::fake();
    Notification::fake();

    // Should not throw
    dispatch_sync(new PublishDigest(99999));

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('does nothing gracefully when the list has no output destination for webpage type', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => null, // misconfigured
    ]);

    // Ensure there are summaries to process
    $feedId = DB::table('text_based_rss_feeds')->insertGetId([
        'user_id' => $user->id, 'title' => 'Feed', 'rss_url' => 'https://example.com/feed',
        'enabled' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $lsId = DB::table('list_sources')->insertGetId([
        'list_id' => $list->id, 'sourceable_id' => $feedId,
        'sourceable_type' => 'text_based_rss_feed', 'enabled' => true,
        'suspended' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('summaries')->insert([
        'user_id' => $user->id, 'list_source_id' => $lsId,
        'source_url' => 'https://example.com/x', 'source_title' => 'X',
        'processing_mode' => 'description', 'summary_html' => '<p>x</p>',
        'is_relevant' => true, 'included_in_digest' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Should not throw
    dispatch_sync(new PublishDigest($list->id));

    // Summaries should NOT be marked as included since delivery failed
    expect(DB::table('summaries')->where('included_in_digest', true)->count())->toBe(0);
});