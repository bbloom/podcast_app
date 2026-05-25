<?php

// =============================================================================
// CloudflareBuildStatusService
//
// Checks the current status of a Cloudflare Pages deployment by calling the
// Cloudflare REST API.
//
// This service is the read-side complement to DeployHookTriggerService, which
// handles the write-side (triggering a build). The two services are
// deliberately separate — triggering and status-checking are independent
// concerns.
//
// API endpoint:
//   GET https://api.cloudflare.com/client/v4/accounts/{account_id}
//            /pages/projects/{project_name}/deployments/{deployment_id}
//
// Credentials are read from config/podcast_post_production.php:
//   cloudflare.account_id — the Cloudflare account identifier
//   cloudflare.api_token  — a scoped API token with Account / Pages / Read
//
// The project_name is derived at runtime from the hook's triggerable slug
// (e.g. PodcastShow->slug = "bob-bloom-show"). No extra column is needed.
//
// The deployment_id is read from the hook's last_build_id column, which is
// populated by DeployHookTriggerService when a build is successfully triggered.
//
// This service does not modify the database — it is a pure read operation.
// Status persistence (if needed) is the caller's responsibility.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Services/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class CloudflareBuildStatusService
{
    private const API_BASE = 'https://api.cloudflare.com/client/v4';

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  checkStatus()                                                         │
    // └────────────────────────────────────────────────────────────────────────┘

    /**
     * Check the current deployment status for the given hook's last build.
     *
     * Returns a CloudflareBuildStatusResult value object. The caller uses
     * this to update the UI or advance the episode pipeline.
     *
     * Guards:
     *   - Hook must be a Cloudflare Pages hook.
     *   - Hook must have a last_build_id (i.e. a build has been triggered).
     *
     * Both guard failures return a CloudflareBuildStatusResult::failure()
     * with a descriptive message — they are not exceptions.
     */
    public function checkStatus(DeployHook $hook): CloudflareBuildStatusResult
    {
        // ── Guard: Cloudflare Pages only ──────────────────────────────────────
        if ($hook->provider !== DeployHookProvider::cloudflare_pages) {
            return CloudflareBuildStatusResult::failure(
                hook:         $hook,
                httpStatus:   0,
                errorMessage: 'Build status checks are only supported for Cloudflare Pages hooks.',
            );
        }

        // ── Guard: a build must have been triggered ───────────────────────────
        if (! $hook->last_build_id) {
            return CloudflareBuildStatusResult::failure(
                hook:         $hook,
                httpStatus:   0,
                errorMessage: 'No build has been triggered for this hook yet.',
            );
        }

        // ── Resolve credentials and identifiers ───────────────────────────────
        $accountId    = config('podcast_post_production.cloudflare.account_id');
        $apiToken     = config('podcast_post_production.cloudflare.api_token');
        $projectName  = $hook->triggerable->slug;
        $deploymentId = $hook->last_build_id;

        $url = self::API_BASE
            . "/accounts/{$accountId}"
            . "/pages/projects/{$projectName}"
            . "/deployments/{$deploymentId}";

        // ── Call the Cloudflare API ───────────────────────────────────────────
        try {
            $response = Http::withToken($apiToken)
                ->timeout(15)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('CloudflareBuildStatusService: network error.', [
                'hook_id'      => $hook->id,
                'hook_label'   => $hook->label,
                'deployment_id'=> $deploymentId,
                'error'        => $e->getMessage(),
            ]);

            return CloudflareBuildStatusResult::failure(
                hook:         $hook,
                httpStatus:   0,
                errorMessage: 'Could not reach Cloudflare. Error: ' . $e->getMessage(),
            );
        }

        // ── Non-success HTTP response ─────────────────────────────────────────
        if (! $response->successful()) {
            $error = $response->json('errors.0.message')
                ?? ('HTTP ' . $response->status());

            Log::warning('CloudflareBuildStatusService: API returned an error.', [
                'hook_id'      => $hook->id,
                'hook_label'   => $hook->label,
                'deployment_id'=> $deploymentId,
                'http_status'  => $response->status(),
                'error'        => $error,
            ]);

            return CloudflareBuildStatusResult::failure(
                hook:         $hook,
                httpStatus:   $response->status(),
                errorMessage: 'Cloudflare returned an error: ' . $error,
            );
        }

        // ── Parse the deployment stage data ───────────────────────────────────
        //
        // Cloudflare returns the current pipeline position via latest_stage:
        //   result.latest_stage.name   — e.g. "build", "deploy"
        //   result.latest_stage.status — e.g. "active", "success", "failure"
        //
        $body               = $response->json();
        $latestStage        = $body['result']['latest_stage'] ?? [];
        $currentStage       = $latestStage['name']   ?? 'unknown';
        $currentStageStatus = $latestStage['status'] ?? 'unknown';

        return CloudflareBuildStatusResult::success(
            hook:               $hook,
            httpStatus:         $response->status(),
            currentStage:       $currentStage,
            currentStageStatus: $currentStageStatus,
        );
    }
}