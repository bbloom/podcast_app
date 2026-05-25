<?php

namespace Tests\Unit\MEDIA_PLATFORM\StaticSiteDeployHooks;

use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\CloudflareBuildStatusResult;
use Tests\TestCase;

// =============================================================================
// CloudflareBuildStatusResultTest
//
// Unit tests for the CloudflareBuildStatusResult value object.
//
// This is a pure PHP test — no database, no HTTP. The object is constructed
// directly via its named static factories and its state methods are verified
// against every meaningful stage/status combination.
//
// First unit test in the suite. All other tests are feature tests.
// =============================================================================

class CloudflareBuildStatusResultTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function successResult(string $stage, string $stageStatus): CloudflareBuildStatusResult
    {
        return CloudflareBuildStatusResult::success(
            hook:               new DeployHook(),
            httpStatus:         200,
            currentStage:       $stage,
            currentStageStatus: $stageStatus,
        );
    }

    private function failureResult(int $httpStatus = 500, string $message = 'Something went wrong'): CloudflareBuildStatusResult
    {
        return CloudflareBuildStatusResult::failure(
            hook:         new DeployHook(),
            httpStatus:   $httpStatus,
            errorMessage: $message,
        );
    }

    // ── apiCallSucceeded() ────────────────────────────────────────────────────

    public function test_success_factory_sets_api_call_succeeded_to_true(): void
    {
        $this->assertTrue($this->successResult('build', 'active')->apiCallSucceeded());
    }

    public function test_failure_factory_sets_api_call_succeeded_to_false(): void
    {
        $this->assertFalse($this->failureResult()->apiCallSucceeded());
    }

    // ── isPending() ───────────────────────────────────────────────────────────

    public function test_is_pending_while_queued_and_idle(): void
    {
        $this->assertTrue($this->successResult('queued', 'idle')->isPending());
    }

    public function test_is_pending_while_build_stage_is_active(): void
    {
        $this->assertTrue($this->successResult('build', 'active')->isPending());
    }

    public function test_is_pending_while_initialize_stage_is_active(): void
    {
        $this->assertTrue($this->successResult('initialize', 'active')->isPending());
    }

    public function test_is_not_pending_when_deploy_stage_succeeds(): void
    {
        $this->assertFalse($this->successResult('deploy', 'success')->isPending());
    }

    public function test_is_not_pending_when_build_stage_fails(): void
    {
        $this->assertFalse($this->successResult('build', 'failure')->isPending());
    }

    public function test_is_not_pending_when_canceled(): void
    {
        $this->assertFalse($this->successResult('initialize', 'canceled')->isPending());
    }

    public function test_is_not_pending_when_api_call_failed(): void
    {
        $this->assertFalse($this->failureResult()->isPending());
    }

    // ── isComplete() ──────────────────────────────────────────────────────────

    public function test_is_complete_when_deploy_stage_succeeds(): void
    {
        $this->assertTrue($this->successResult('deploy', 'success')->isComplete());
    }

    public function test_is_complete_when_any_stage_has_failure_status(): void
    {
        $this->assertTrue($this->successResult('build', 'failure')->isComplete());
    }

    public function test_is_complete_when_canceled(): void
    {
        $this->assertTrue($this->successResult('clone_repo', 'canceled')->isComplete());
    }

    public function test_is_not_complete_when_stage_is_active(): void
    {
        $this->assertFalse($this->successResult('build', 'active')->isComplete());
    }

    public function test_is_not_complete_when_stage_is_idle(): void
    {
        $this->assertFalse($this->successResult('queued', 'idle')->isComplete());
    }

    public function test_is_not_complete_when_api_call_failed(): void
    {
        $this->assertFalse($this->failureResult()->isComplete());
    }

    // ── buildSucceeded() ──────────────────────────────────────────────────────

    public function test_build_succeeded_when_deploy_stage_is_success(): void
    {
        $this->assertTrue($this->successResult('deploy', 'success')->buildSucceeded());
    }

    public function test_build_did_not_succeed_when_intermediate_stage_succeeds(): void
    {
        // The build stage completed but the final deploy stage has not run yet.
        $this->assertFalse($this->successResult('build', 'success')->buildSucceeded());
    }

    public function test_build_did_not_succeed_when_deploy_stage_fails(): void
    {
        $this->assertFalse($this->successResult('deploy', 'failure')->buildSucceeded());
    }

    public function test_build_did_not_succeed_when_still_pending(): void
    {
        $this->assertFalse($this->successResult('build', 'active')->buildSucceeded());
    }

    public function test_build_did_not_succeed_when_api_call_failed(): void
    {
        $this->assertFalse($this->failureResult()->buildSucceeded());
    }

    // ── buildFailed() ────────────────────────────────────────────────────────

    public function test_build_failed_when_stage_has_failure_status(): void
    {
        $this->assertTrue($this->successResult('build', 'failure')->buildFailed());
    }

    public function test_build_failed_when_canceled(): void
    {
        $this->assertTrue($this->successResult('initialize', 'canceled')->buildFailed());
    }

    public function test_build_failed_when_deploy_stage_itself_fails(): void
    {
        $this->assertTrue($this->successResult('deploy', 'failure')->buildFailed());
    }

    public function test_build_did_not_fail_when_deploy_succeeds(): void
    {
        $this->assertFalse($this->successResult('deploy', 'success')->buildFailed());
    }

    public function test_build_did_not_fail_when_still_pending(): void
    {
        $this->assertFalse($this->successResult('build', 'active')->buildFailed());
    }

    public function test_build_did_not_fail_when_api_call_failed(): void
    {
        // An API-level failure is distinct from the build itself failing.
        $this->assertFalse($this->failureResult()->buildFailed());
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function test_current_stage_and_status_are_accessible_on_success(): void
    {
        $result = $this->successResult('build', 'active');

        $this->assertSame('build', $result->currentStage());
        $this->assertSame('active', $result->currentStageStatus());
    }

    public function test_current_stage_and_status_are_null_on_api_failure(): void
    {
        $result = $this->failureResult();

        $this->assertNull($result->currentStage());
        $this->assertNull($result->currentStageStatus());
    }

    public function test_error_message_is_null_on_success(): void
    {
        $this->assertNull($this->successResult('deploy', 'success')->errorMessage());
    }

    public function test_error_message_is_set_on_failure(): void
    {
        $result = $this->failureResult(message: 'Invalid API token');

        $this->assertSame('Invalid API token', $result->errorMessage());
    }

    public function test_http_status_is_200_on_success(): void
    {
        $this->assertSame(200, $this->successResult('build', 'active')->httpStatus());
    }

    public function test_http_status_is_zero_for_network_level_failure(): void
    {
        $result = $this->failureResult(httpStatus: 0, message: 'Connection refused');

        $this->assertSame(0, $result->httpStatus());
    }

    public function test_http_status_reflects_cloudflare_error_code(): void
    {
        $result = $this->failureResult(httpStatus: 403);

        $this->assertSame(403, $result->httpStatus());
    }
}