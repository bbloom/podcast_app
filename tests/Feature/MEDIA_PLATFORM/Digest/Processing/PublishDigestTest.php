<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/PublishDigestTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\Processing\Jobs\PublishDigest;
use MediaPlatform\Digest\Processing\Services\DigestBuilderService;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use MediaPlatform\Digest\Publishing\Mail\DigestMailable;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest as PublishedDigestModel;
use MediaPlatform\Digest\Publishing\Notifications\DigestEmptyNotification;
use MediaPlatform\Digest\Publishing\Notifications\DigestReadyNotification;
use MediaPlatform\Digest\Publishing\Notifications\StaticSiteDigestReadyNotification;
use MediaPlatform\Digest\Publishing\Services\DeliveryStrategyResolver;
use MediaPlatform\Digest\Publishing\Services\DigestRetentionService;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerResult;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishDigestTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function insertPendingSummary(int $userId, int $listSourceId): int
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

    private function fakeDigestData(ListModel $list): array
    {
        return [
            'list'         => $list,
            'date'         => now(),
            'groups'       => collect([[
                'source_id'   => 1,
                'source_name' => 'Test Feed',
                'source_type' => 'text_based_rss_feed',
                'items'       => collect([(object) [
                    'source_url'          => 'https://example.com/article-1',
                    'source_title'        => 'Test Article',
                    'source_description'  => 'A test article.',
                    'source_published_at' => now()->subHour(),
                    'summary_html'        => '<p>Summary.</p>',
                ]]),
            ]]),
            'total_items'  => 1,
            'source_count' => 1,
        ];
    }

    private function runPublishDigest(int $listId, DigestBuilderService $builder): void
    {
        (new PublishDigest($listId))->handle(
            app(ProcessingGate::class),
            $builder,
            app(DeliveryStrategyResolver::class),
            app(DigestRetentionService::class),
        );
    }

    private function makeSftpDest(User $user): OutputDestination
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

    private function mockSftp(array $uploadReturn = ['success' => true, 'path' => '/digests/test']): void
    {
        $sftp = Mockery::mock(SftpService::class);
        $sftp->shouldReceive('upload')->andReturn($uploadReturn);
        app()->instance(SftpService::class, $sftp);
    }

    private function mockTriggerService(bool $succeeds = true): void
    {
        $service = Mockery::mock(DeployHookTriggerService::class);
        $service->shouldReceive('trigger')->andReturnUsing(function (DeployHook $hook) use ($succeeds) {
            return $succeeds
                ? DeployHookTriggerResult::success($hook, 200, 'build-123')
                : DeployHookTriggerResult::failure($hook, 500, 'Provider error');
        });
        app()->instance(DeployHookTriggerService::class, $service);
    }

    // =========================================================================
    // GROUP 1: List not found
    // =========================================================================

    #[Test]
    public function aborts_gracefully_when_the_list_does_not_exist(): void
    {
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldNotReceive('build');

        $this->runPublishDigest(99999, $builder);

        $this->assertTrue(true);
    }

    // =========================================================================
    // GROUP 2: Gate blocked
    // =========================================================================

    #[Test]
    public function skips_delivery_when_gate_is_blocked_for_webpage_list(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();
        $dest = $this->makeSftpDest($user);
        $list = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['enabled' => true]);

        DB::table('admin_alerts')->insert([
            'tier' => 3, 'category' => 'infrastructure',
            'title' => 'Infrastructure down', 'message' => 'Test block.',
            'is_resolved' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldNotReceive('build');

        $this->mockSftp();
        $this->runPublishDigest($list->id, $builder);

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    #[Test]
    public function does_NOT_skip_delivery_when_gate_is_blocked_for_email_list(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email, 'enabled' => true]);

        DB::table('admin_alerts')->insert([
            'tier' => 3, 'category' => 'infrastructure',
            'title' => 'Infrastructure down', 'message' => 'Test block.',
            'is_resolved' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->once()->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('markAsIncluded')->once();

        $this->runPublishDigest($list->id, $builder);

        Mail::assertSent(DigestMailable::class);
    }

    #[Test]
    public function skips_delivery_when_gate_is_blocked_for_static_site_list(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DB::table('admin_alerts')->insert([
            'tier' => 3, 'category' => 'infrastructure',
            'title' => 'Infrastructure down', 'message' => 'Test block.',
            'is_resolved' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldNotReceive('build');

        $this->runPublishDigest($list->id, $builder);
        $this->assertTrue(true);
    }

    // =========================================================================
    // GROUP 3: Empty digest
    // =========================================================================

    #[Test]
    public function sends_DigestEmptyNotification_when_build_returns_null(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->once()->andReturn(null);
        $builder->shouldNotReceive('markAsIncluded');

        $this->runPublishDigest($list->id, $builder);

        Notification::assertSentTo($user, DigestEmptyNotification::class);
    }

    #[Test]
    public function updates_last_run_at_even_when_digest_is_empty(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn(null);

        $this->runPublishDigest($list->id, $builder);

        $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
    }

    // =========================================================================
    // GROUP 4: Email delivery
    // =========================================================================

    #[Test]
    public function sends_DigestMailable_for_email_list(): void
    {
        Mail::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->once()->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('markAsIncluded')->once();

        $this->runPublishDigest($list->id, $builder);

        Mail::assertSent(DigestMailable::class, fn ($m) => $m->hasTo($user->email));
    }

    #[Test]
    public function calls_markAsIncluded_after_successful_email_delivery(): void
    {
        Mail::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('markAsIncluded')->once();

        $this->runPublishDigest($list->id, $builder);
    }

    #[Test]
    public function does_not_call_markAsIncluded_when_email_delivery_fails(): void
    {
        Mail::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP failure'));

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldNotReceive('markAsIncluded');

        $this->runPublishDigest($list->id, $builder);
    }

    #[Test]
    public function updates_last_run_at_after_successful_email_delivery(): void
    {
        Mail::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('markAsIncluded');

        $this->runPublishDigest($list->id, $builder);

        $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
    }

    // =========================================================================
    // GROUP 5: Webpage (SFTP) delivery
    // =========================================================================

    #[Test]
    public function uploads_via_SFTP_for_webpage_list(): void
    {
        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded')->once();

        $this->mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);
        $this->runPublishDigest($list->id, $builder);
        $this->assertTrue(true);
    }

    #[Test]
    public function does_not_call_markAsIncluded_when_SFTP_upload_fails(): void
    {
        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldNotReceive('markAsIncluded');

        $this->mockSftp(['success' => false, 'message' => 'Connection refused']);
        $this->runPublishDigest($list->id, $builder);
    }

    #[Test]
    public function aborts_with_error_when_webpage_list_has_no_output_destination(): void
    {
        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Webpage, 'output_destination_id' => null]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldNotReceive('markAsIncluded');

        $this->runPublishDigest($list->id, $builder);
        $this->assertTrue(true);
    }

    #[Test]
    public function sends_DigestReadyNotification_after_SFTP_upload_when_notify_by_email_is_true(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => true]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);
        $this->runPublishDigest($list->id, $builder);

        Notification::assertSentTo($user, DigestReadyNotification::class);
    }

    #[Test]
    public function does_not_send_DigestReadyNotification_when_notify_by_email_is_false(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create(['notify_by_email' => false]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('my-list-digest-2026-03-25');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockSftp(['success' => true, 'path' => '/digests/my-list-digest-2026-03-25']);
        $this->runPublishDigest($list->id, $builder);

        Notification::assertNotSentTo($user, DigestReadyNotification::class);
    }

    // =========================================================================
    // GROUP 6: markAsIncluded — integration
    // =========================================================================

    #[Test]
    public function marks_summaries_as_included_in_DB_after_successful_email_delivery(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->create(['output_type' => OutputType::Email]);

        $feedId = DB::table('text_based_rss_feeds')->insertGetId([
            'user_id' => $user->id, 'title' => 'Test Feed',
            'rss_url' => 'https://example.com/feed.xml', 'enabled' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $listSourceId = DB::table('list_sources')->insertGetId([
            'list_id' => $list->id, 'sourceable_id' => $feedId,
            'sourceable_type' => 'text_based_rss_feed', 'enabled' => true,
            'suspended' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $summaryId = $this->insertPendingSummary($user->id, $listSourceId);

        $this->runPublishDigest($list->id, app(DigestBuilderService::class));

        $summary = DB::table('summaries')->find($summaryId);
        $this->assertTrue((bool) $summary->included_in_digest);
        $this->assertNotNull($summary->included_in_digest_at);
    }

    // =========================================================================
    // GROUP 7: last_run_at is always updated
    // =========================================================================

    #[Test]
    public function updates_last_run_at_after_successful_webpage_delivery(): void
    {
        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('slug');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockSftp(['success' => true, 'path' => '/digests/slug']);
        $this->runPublishDigest($list->id, $builder);

        $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
    }

    #[Test]
    public function does_not_update_last_run_at_when_delivery_fails(): void
    {
        $user    = User::factory()->create();
        $dest    = $this->makeSftpDest($user);
        $list    = ListModel::factory()->forUser($user)->webpage($dest->id)->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('slug');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');

        $this->mockSftp(['success' => false, 'message' => 'Timeout']);
        $this->runPublishDigest($list->id, $builder);

        $this->assertNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
    }

    // =========================================================================
    // GROUP 8: Static site delivery
    // =========================================================================

    #[Test]
    public function persists_a_PublishedDigest_record_for_static_site_list(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded')->once();

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        $this->assertDatabaseHas('published_digests', [
            'list_id' => $list->id, 'user_id' => $user->id,
            'slug' => 'test-digest-2026-04-18', 'total_items' => 1,
        ]);
    }

    #[Test]
    public function fires_deploy_hooks_after_persisting_PublishedDigest(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true]);

        $triggerService = Mockery::mock(DeployHookTriggerService::class);
        $triggerService->shouldReceive('trigger')->once()->andReturnUsing(
            fn (DeployHook $h) => DeployHookTriggerResult::success($h, 200, 'build-456')
        );
        app()->instance(DeployHookTriggerService::class, $triggerService);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->runPublishDigest($list->id, $builder);

        $this->assertNotNull(PublishedDigestModel::where('list_id', $list->id)->first()->deploy_hook_fired_at);
    }

    #[Test]
    public function calls_markAsIncluded_after_successful_static_site_delivery(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded')->once();

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);
    }

    #[Test]
    public function sends_StaticSiteDigestReadyNotification_when_notify_by_email_is_true(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => true]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        Notification::assertSentTo($user, StaticSiteDigestReadyNotification::class);
    }

    #[Test]
    public function does_not_send_static_site_notification_when_notify_by_email_is_false(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create(['notify_by_email' => false]);
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        Notification::assertNotSentTo($user, StaticSiteDigestReadyNotification::class);
    }

    #[Test]
    public function updates_last_run_at_after_successful_static_site_delivery(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        $this->assertNotNull(DB::table('lists')->where('id', $list->id)->value('last_run_at'));
    }

    #[Test]
    public function prunes_old_PublishedDigest_records_beyond_retention_count(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create(['retention_count' => 2]);

        PublishedDigestModel::factory()->forList($list)->create(['slug' => 'old-1', 'digest_date' => now()->subDays(3)]);
        PublishedDigestModel::factory()->forList($list)->create(['slug' => 'old-2', 'digest_date' => now()->subDays(2)]);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('new-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        $this->assertSame(2, PublishedDigestModel::where('list_id', $list->id)->count());
        $this->assertDatabaseHas('published_digests', ['slug' => 'new-digest-2026-04-18']);
        $this->assertDatabaseMissing('published_digests', ['slug' => 'old-1']);
    }

    #[Test]
    public function auto_enables_the_API_when_processing_a_static_site_list(): void
    {
        Notification::fake();

        ApiControl::instance()->disable();
        $this->assertFalse(ApiControl::getStatus());

        $user    = User::factory()->create();
        $list    = ListModel::factory()->forUser($user)->staticSite()->create();
        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded');

        $this->mockTriggerService();
        $this->runPublishDigest($list->id, $builder);

        $this->assertTrue(ApiControl::getStatus());
    }

    #[Test]
    public function logs_deploy_hook_failure_but_still_returns_true_when_data_is_persisted(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $list = ListModel::factory()->forUser($user)->staticSite()->create();

        DeployHook::factory()->create(['triggerable_type' => 'digest_list', 'triggerable_id' => $list->id, 'enabled' => true]);

        $builder = Mockery::mock(DigestBuilderService::class);
        $builder->shouldReceive('build')->andReturn($this->fakeDigestData($list));
        $builder->shouldReceive('buildSlug')->andReturn('test-digest-2026-04-18');
        $builder->shouldReceive('buildExcerpt')->andReturn('1 item from 1 source');
        $builder->shouldReceive('markAsIncluded')->once();

        $this->mockTriggerService(succeeds: false);
        $this->runPublishDigest($list->id, $builder);

        $this->assertDatabaseHas('published_digests', ['slug' => 'test-digest-2026-04-18']);
    }
}