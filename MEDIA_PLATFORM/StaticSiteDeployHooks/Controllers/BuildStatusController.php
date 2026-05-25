<?php

// =============================================================================
// BuildStatusController
//
// Lightweight JSON endpoint polled by Alpine.js to check the current status
// of a Cloudflare Pages deployment for a given deploy hook.
//
// Delegates all Cloudflare API logic to CloudflareBuildStatusService and
// returns a flat JSON payload for the front-end to consume.
//
// Used in two contexts:
//   1. The deploy hook show page — user-initiated status check.
//   2. The BuildConfirmation pipeline step — automatic polling while the
//      user waits for the static site build to complete.
//
// Ownership is enforced via the hook's triggerable — a deploy hook has no
// direct user_id column, so ownership is resolved through the related model.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Controllers/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Services\CloudflareBuildStatusService;

class BuildStatusController extends Controller
{
    /**
     * Return the current Cloudflare Pages build status for the given hook.
     *
     * Alpine.js polls this endpoint every few seconds. The payload gives the
     * front-end everything it needs to show a spinner, advance the pipeline,
     * or display a failure state.
     *
     * Ownership: the hook's triggerable must belong to the authenticated user.
     */
    public function __invoke(DeployHook $deploy_hook, CloudflareBuildStatusService $service): JsonResponse
    {
        abort_if($deploy_hook->triggerable->user_id !== auth()->id(), 403);

        $result = $service->checkStatus($deploy_hook);

        return response()->json([
            'api_call_succeeded'   => $result->apiCallSucceeded(),
            'is_pending'           => $result->isPending(),
            'build_succeeded'      => $result->buildSucceeded(),
            'build_failed'         => $result->buildFailed(),
            'current_stage'        => $result->currentStage(),
            'current_stage_status' => $result->currentStageStatus(),
            'error_message'        => $result->errorMessage(),
        ]);
    }
}