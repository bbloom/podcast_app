<?php

namespace Tests\Feature\MEDIA_PLATFORM\StaticSiteDeployHooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\CloudflareBuildStatusService;
use Tests\TestCase;

class CloudflareBuildStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    // ── Setup ─────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent any real HTTP calls from silently slipping through.
        Http::preventStrayRequests();

        // Default test credentials — overridden per-test where needed.
        config([
            'podcast_post_production.cloudflare.account_id' => 'test-account-id',
            'podcast_post_production.cloudflare.api_token'  => 'test-api-token',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function service(): CloudflareBuildStatusService
    {
        return app(CloudflareBuildStatusService::class);
    }

    /**
     * Create a show and a Cloudflare Pages hook that has been triggered.
     * The hook's triggerable relation is pre-loaded to avoid a second query.
     */
    private function showAndHook(string $buildId = 'test-build-id'): array
    {
        $show = PodcastShow::factory()->create();
        $hook = DeployHook::factory()->succeeded($buildId)->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
        ]);
        $hook->setRelation('triggerable', $show);

        return [$show, $hook];
    }

    /**
     * Fake a successful Cloudflare API response for the given stage and status.
     */
    private function fakeStage(string $stage, string $stageStatus): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'result'  => ['latest_stage' => ['name' => $stage, 'status' => $stageStatus]],
                'success' => true,
            ], 200),
        ]);
    }

    // ── Guards ────────────────────────────────────────────────────────────────

    public function test_returns_failure_for_non_cloudflare_hook(): void
    {
        $show = PodcastShow::factory()->create();
        $hook = DeployHook::factory()->netlify()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
        ]);
        $hook->setRelation('triggerable', $show);

        $result = $this->service()->checkStatus($hook);

        $this->assertFalse($result->apiCallSucceeded());
        $this->assertNotNull($result->errorMessage());
    }

    public function test_returns_failure_when_hook_has_no_build_id(): void
    {
        $show = PodcastShow::factory()->create();
        $hook = DeployHook::factory()->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
            'last_build_id'    => null,
        ]);
        $hook->setRelation('triggerable', $show);

        $result = $this->service()->checkStatus($hook);

        $this->assertFalse($result->apiCallSucceeded());
        $this->assertNotNull($result->errorMessage());
    }

    // ── Successful API responses ──────────────────────────────────────────────

    public function test_returns_pending_result_when_build_stage_is_active(): void
    {
        $this->fakeStage('build', 'active');
        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertTrue($result->apiCallSucceeded());
        $this->assertTrue($result->isPending());
        $this->assertFalse($result->buildSucceeded());
        $this->assertFalse($result->buildFailed());
        $this->assertSame('build', $result->currentStage());
        $this->assertSame('active', $result->currentStageStatus());
    }

    public function test_returns_pending_result_when_queued(): void
    {
        $this->fakeStage('queued', 'idle');
        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertTrue($result->apiCallSucceeded());
        $this->assertTrue($result->isPending());
    }

    public function test_returns_succeeded_result_when_deploy_stage_is_success(): void
    {
        $this->fakeStage('deploy', 'success');
        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertTrue($result->apiCallSucceeded());
        $this->assertTrue($result->buildSucceeded());
        $this->assertFalse($result->isPending());
        $this->assertFalse($result->buildFailed());
    }

    public function test_returns_failed_result_when_build_stage_fails(): void
    {
        $this->fakeStage('build', 'failure');
        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertTrue($result->apiCallSucceeded());
        $this->assertTrue($result->buildFailed());
        $this->assertFalse($result->buildSucceeded());
        $this->assertFalse($result->isPending());
    }

    // ── API error responses ───────────────────────────────────────────────────

    public function test_returns_failure_when_cloudflare_returns_401(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => false,
                'errors'  => [['message' => 'Invalid API token']],
            ], 401),
        ]);

        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertFalse($result->apiCallSucceeded());
        $this->assertSame(401, $result->httpStatus());
        $this->assertStringContainsString('Invalid API token', $result->errorMessage());
    }

    public function test_returns_failure_when_cloudflare_returns_404(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => false,
                'errors'  => [['message' => 'Deployment not found']],
            ], 404),
        ]);

        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertFalse($result->apiCallSucceeded());
        $this->assertSame(404, $result->httpStatus());
    }

    public function test_returns_failure_on_network_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        [, $hook] = $this->showAndHook();

        $result = $this->service()->checkStatus($hook);

        $this->assertFalse($result->apiCallSucceeded());
        $this->assertSame(0, $result->httpStatus());
        $this->assertStringContainsString('Connection timed out', $result->errorMessage());
    }

    // ── Request construction ──────────────────────────────────────────────────

    public function test_api_request_uses_show_slug_as_project_name(): void
    {
        $this->fakeStage('deploy', 'success');

        $show = PodcastShow::factory()->create(['slug' => 'my-podcast-show']);
        $hook = DeployHook::factory()->succeeded('my-build-id')->create([
            'triggerable_type' => 'podcast_show',
            'triggerable_id'   => $show->id,
        ]);
        $hook->setRelation('triggerable', $show);

        $this->service()->checkStatus($hook);

        Http::assertSent(fn ($req) =>
            str_contains($req->url(), '/pages/projects/my-podcast-show/') &&
            str_contains($req->url(), '/deployments/my-build-id')
        );
    }

    public function test_api_request_uses_account_id_from_config(): void
    {
        config(['podcast_post_production.cloudflare.account_id' => 'my-account-abc']);
        $this->fakeStage('deploy', 'success');

        [, $hook] = $this->showAndHook();

        $this->service()->checkStatus($hook);

        Http::assertSent(fn ($req) =>
            str_contains($req->url(), '/accounts/my-account-abc/')
        );
    }

    public function test_api_request_sends_bearer_token_from_config(): void
    {
        config(['podcast_post_production.cloudflare.api_token' => 'my-secret-token']);
        $this->fakeStage('deploy', 'success');

        [, $hook] = $this->showAndHook();

        $this->service()->checkStatus($hook);

        Http::assertSent(fn ($req) =>
            $req->header('Authorization')[0] === 'Bearer my-secret-token'
        );
    }
}