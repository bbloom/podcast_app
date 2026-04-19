<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/PublishDigestTest.php

use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\Processing\Jobs\PublishDigest;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use MediaPlatform\Digest\Publishing\Contracts\DigestDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest as PublishedDigestModel;
use MediaPlatform\Digest\Publishing\Notifications\DigestEmptyNotification;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Publishing\Notifications\StaticSiteDigestReadyNotification;
use MediaPlatform\Digest\Publishing\Services\DeliveryStrategyResolver;
use MediaPlatform\Digest\Publishing\Strategies\EmailDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Strategies\StaticSiteDeliveryStrategy;
use MediaPlatform\Digest\Publishing\Strategies\WebpageDeliveryStrategy;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

use MediaPlatform\Digest\Publishing\Services\DigestRetentionService;

/**
 * PublishDigestTest
 *
 * Tests PublishDigest::handle() directly (synchronously) so that we exercise
 * the full logic without needing a real queue worker.
 *
 * APPROACH
 * ────────
 * - DigestBuilderService is mocked to control what build() returns.
 * - SftpService is mocked (bound via container) to avoid real network calls.
 * - DeployHookTriggerService is mocked for static site tests.
 * - ProcessingGate is bound to the real implementation (uses the DB for alerts).
 * - Mail::fake() and Notification::fake() intercept deliveries.
 *
 * TEST GROUPS
 * ───────────
 *   1.  List not found — aborts gracefully
 *   2.  Gate blocked — skips delivery for non-email lists
 *   3.  Empty digest — sends DigestEmptyNotification, updates last_run_at
 *   4.  Email delivery — happy path, failure, markAsIncluded behaviour
 *   5.  Webpage delivery — happy path, SFTP failure, notify_by_email flag
 *   6.  markAsIncluded — integration (real DigestBuilderService)
 *   7.  last_run_at — always updated on success, even for empty digest
 *   8.  Static site delivery — persist, deploy hooks, notifications, pruning, API guard
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

/**
 * Insert a pending summary row directly via DB and return its ID.
 */
function insertPendingSummary(int $userId, int $listSourceId): int
{
    return DB::table('summaries')->insertGetId([
        'user_id'            => $userId,
        'list_source_id'     => $listSourceId,
        'source_url'         => 'https://example.com/article-1',
        'source_title'       => 'Test Article',
        'processing_mode'    => 'description',
        'summary_html'       => '<p>Summary.</p>',
        'is_relevant'        => true,
        'included_in_digest' => false,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
}

/**
 * Build a minimal fake $digestData array matching what DigestBuilderService::build() returns.
 */
function fakeDigestData(ListModel $list): array
{
    return [
        'list'         => $list,
        'date'         => now(),
        'groups'       => collect([
            [
                'source_id'   => 1,
                'source_name' => 'Test Feed',
                'source_type' => 'text_based_rss_feed',
                'items'       => collect([
                    (object) [
                        'source_url'          => 'https://example.com/article-1',
                        'source_title'        => 'Test Article',
                        'source_description'  => 'A test article.',
                        'source_published_at' => now()->subHour(),
                        'summary_html'        => '<p>Summary.</p>',
                    ],
                ]),
            ],
        ]),
        'total_items'  => 1,
        'source_count' => 1,
    ];
}

/**
 * Run PublishDigest::handle() synchronously with the given mocked builder.
 *
 * SftpService and DeployHookTriggerService are resolved from the container,
 * so bind mocks there before calling this if needed.
 */
function runPublishDigest(int $listId, DigestBuilderService $builder): void
{
    (new PublishDigest($listId))->handle(
        app(ProcessingGate::class),
        $builder,
        app(DeliveryStrategyResolver::class),
        app(DigestRetentionService::class),
    );
}

/**
 * Create an SFTP OutputDestination for a user.
 */
function makeSftpDest(User $user): OutputDestination
{
    return OutputDestination::factory()->forUser($user)->create([
        'type'      => 'sftp',
        'host'      => 'sftp.example.com',
        'port'      => 22,
        'username'  => 'deploy',
        'auth_type' => 'password',
        'password'  => 'secret',
        'path'      => '/digests',
        'base_url'  => 'https://example.com/digests',
    ]);
}

/**
 * Bind a mocked SftpService into the container.
 */
function mockSftp(array $uploadReturn = ['success' => true, 'path' => '/digests/test']): void
{
    $sftp = mock(SftpService::class);
    $sftp->shouldReceive('upload')->andReturn($uploadReturn);
    app()->instance(SftpService::class, $sftp);
}

/**
 * Bind a mocked DeployHookTriggerService into the container.
 */
function mockTriggerService(bool $succeeds = true): void
{
    $service = mock(DeployHookTriggerService::class);
    $service->shouldReceive('trigger')->andReturnUsing(function (DeployHook $hook) use ($succeeds) {
        if ($succeeds) {
            return DeployHookTriggerResult::success($hook, 200, 'build-123');
        }
        return DeployHookTriggerResult::failure($hook, 500, 'Provider error');
    });
    app()->instance(DeployHookTriggerService::class, $service);
}

// =============================================================================
// GROUP 1: List not found
// =============================================================================

it('aborts gracefully when the list does not exist', function () {
    $builder = mock(DigestBuilderService::class);
    $builder->shouldNotReceive('build');

    runPublishDigest(99999, $builder);

    expect(true)->toBeTrue();
});

// =============================================================================
// GROUP 2: Gate blocked
// =============================================================================

it('skips delivery when gate is blocked for webpage list', function () {
    Mail::fake();
    Notification::fake();

    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['enabled' => true]);

    DB::table('admin_alerts')->insert([
        'tier'        => 3,
        'category'    => 'infrastructure',
        'title'       => 'Infrastructure down',
        'message'     => 'Test block.',
        'is_resolved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldNotReceive('build');

    mockSftp();

    runPublishDigest($list->id, $builder);

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('does NOT skip delivery when gate is blocked for email list', function () {
    Mail::fake();
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type' => OutputType::Email,
        'enabled'     => true,
    ]);

    DB::table('admin_alerts')->insert([
        'tier'        => 3,
        'category'    => 'infrastructure',
        'title'       => 'Infrastructure down',
        'message'     => 'Test block.',
        'is_resolved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $digestData = fakeDigestData($list);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->once()->andReturn($digestData);
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);

    Mail::assertSent(DigestMailable::class);
});

it('skips delivery when gate is blocked for static site list', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DB::table('admin_alerts')->insert([
        'tier'        => 3,
        'category'    => 'infrastructure',
        'title'       => 'Infrastructure down',
        'message'     => 'Test block.',
        'is_resolved' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldNotReceive('build');

    runPublishDigest($list->id, $builder);
});

// =============================================================================
// GROUP 3: Empty digest
// =============================================================================

it('sends DigestEmptyNotification when build returns null', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->once()->andReturn(null);
    $builder->shouldNotReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    Notification::assertSentTo($user, DigestEmptyNotification::class);
});

it('updates last_run_at even when digest is empty', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(null);

    runPublishDigest($list->id, $builder);

    $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
});

// =============================================================================
// GROUP 4: Email delivery
// =============================================================================

it('sends DigestMailable for email list', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $digestData = fakeDigestData($list);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->once()->andReturn($digestData);
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);

    Mail::assertSent(DigestMailable::class, fn ($mail) => $mail->hasTo($user->email));
});

it('calls markAsIncluded after successful email delivery', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);
});

it('does not call markAsIncluded when email delivery fails', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP failure'));

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldNotReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);
});

it('updates last_run_at after successful email delivery', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
});

// =============================================================================
// GROUP 5: Webpage (SFTP) delivery
// =============================================================================

it('uploads via SFTP for webpage list', function () {
    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded')->once();

    mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);

    runPublishDigest($list->id, $builder);
});

it('does not call markAsIncluded when SFTP upload fails', function () {
    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldNotReceive('markAsIncluded');

    mockSftp(['success' => false, 'message' => 'Connection refused']);

    runPublishDigest($list->id, $builder);
});

it('aborts with error when webpage list has no output destination', function () {
    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create([
        'output_type'           => OutputType::Webpage,
        'output_destination_id' => null,
    ]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldNotReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);
});

it('sends DigestReadyNotification after SFTP upload when notify_by_email is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => true]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);

    runPublishDigest($list->id, $builder);

    Notification::assertSentTo($user, DigestReadyNotification::class);
});

it('does not send DigestReadyNotification when notify_by_email is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);

    runPublishDigest($list->id, $builder);

    Notification::assertNotSentTo($user, DigestReadyNotification::class);
});

// =============================================================================
// GROUP 6: markAsIncluded — integration (real DigestBuilderService)
// =============================================================================

it('marks summaries as included in DB after successful email delivery', function () {
    Mail::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

    $feedId = DB::table('text_based_rss_feeds')->insertGetId([
        'user_id'    => $user->id,
        'title'      => 'Test Feed',
        'rss_url'    => 'https://example.com/feed.xml',
        'enabled'    => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $listSourceId = DB::table('list_sources')->insertGetId([
        'list_id'         => $list->id,
        'sourceable_id'   => $feedId,
        'sourceable_type' => 'text_based_rss_feed',
        'enabled'         => true,
        'suspended'       => false,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $summaryId = insertPendingSummary($user->id, $listSourceId);

    runPublishDigest($list->id, app(DigestBuilderService::class));

    $summary = DB::table('summaries')->find($summaryId);
    expect($summary->included_in_digest)->toBe(true);
    expect($summary->included_in_digest_at)->not->toBeNull();
});

// =============================================================================
// GROUP 7: last_run_at is always updated
// =============================================================================

it('updates last_run_at after successful webpage delivery', function () {
    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('slug');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    mockSftp(['success' => true, 'path' => '/digests/slug']);

    runPublishDigest($list->id, $builder);

    $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
});

it('does not update last_run_at when delivery fails', function () {
    $user = User::factory()->create();
    $dest = makeSftpDest($user);
    $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('slug');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');

    mockSftp(['success' => false, 'message' => 'Timeout']);

    runPublishDigest($list->id, $builder);

    $this->assertNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
});

// =============================================================================
// GROUP 8: Static site delivery
// =============================================================================

it('persists a PublishedDigest record for static site list', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);

    $this->assertDatabaseHas('published_digests', [
        'list_id'     => $list->id,
        'user_id'     => $user->id,
        'slug'        => 'test-digest-2026-04-18',
        'total_items' => 1,
    ]);
});

it('fires deploy hooks after persisting PublishedDigest', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
    ]);

    $triggerService = mock(DeployHookTriggerService::class);
    $triggerService->shouldReceive('trigger')->once()->andReturnUsing(function (DeployHook $hook) {
        return DeployHookTriggerResult::success($hook, 200, 'build-456');
    });
    app()->instance(DeployHookTriggerService::class, $triggerService);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    $digest = PublishedDigestModel::where('list_id', $list->id)->first();
    expect($digest->deploy_hook_fired_at)->not->toBeNull();
});

it('calls markAsIncluded after successful static site delivery', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);
});

it('sends StaticSiteDigestReadyNotification when notify_by_email is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => true]);

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    Notification::assertSentTo($user, StaticSiteDigestReadyNotification::class);
});

it('does not send static site notification when notify_by_email is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => false]);

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    Notification::assertNotSentTo($user, StaticSiteDigestReadyNotification::class);
});

it('updates last_run_at after successful static site delivery', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
});

it('prunes old PublishedDigest records beyond retention_count', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 2]);

    // Pre-create 2 existing published digests
    PublishedDigestModel::factory()->forList($list)->create(['slug' => 'old-1', 'digest_date' => now()->subDays(3)]);
    PublishedDigestModel::factory()->forList($list)->create(['slug' => 'old-2', 'digest_date' => now()->subDays(2)]);

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('new-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    // Should have 2 records (retention_count = 2): the new one + old-2; old-1 pruned
    expect(PublishedDigestModel::where('list_id', $list->id)->count())->toBe(2);
    $this->assertDatabaseHas('published_digests', ['slug' => 'new-digest-2026-04-18']);
    $this->assertDatabaseMissing('published_digests', ['slug' => 'old-1']);
});

it('auto-enables the API when processing a static site list', function () {
    Notification::fake();

    // Ensure API is off
    ApiControl::instance()->disable();
    expect(ApiControl::getStatus())->toBeFalse();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    mockTriggerService();

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    $builder->shouldReceive('markAsIncluded');

    runPublishDigest($list->id, $builder);

    expect(ApiControl::getStatus())->toBeTrue();
});

it('logs deploy hook failure but still returns true when data is persisted', function () {
    Notification::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->staticSite()->create();

    DeployHook::factory()->create([
        'triggerable_type' => 'digest_list',
        'triggerable_id'   => $list->id,
        'enabled'          => true,
    ]);

    mockTriggerService(succeeds: false);

    $builder = mock(DigestBuilderService::class);
    $builder->shouldReceive('build')->andReturn(fakeDigestData($list));
    $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
    $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
    // markAsIncluded SHOULD still be called — delivery succeeded (data persisted)
    $builder->shouldReceive('markAsIncluded')->once();

    runPublishDigest($list->id, $builder);

    $this->assertDatabaseHas('published_digests', ['slug' => 'test-digest-2026-04-18']);
});