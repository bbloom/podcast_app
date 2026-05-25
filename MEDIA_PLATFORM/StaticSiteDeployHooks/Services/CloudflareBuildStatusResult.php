<?php

// =============================================================================
// CloudflareBuildStatusResult
//
// Immutable value object returned by CloudflareBuildStatusService::checkStatus().
//
// Carries everything the UI and pipeline need to react to a build status check:
//   - whether the Cloudflare API call itself succeeded
//   - the current pipeline stage and its status
//   - convenience booleans for the three end states the caller cares about
//
// Cloudflare Pages deployment stages (in order):
//   queued → initialize → clone_repo → build → deploy
//
// Each stage carries one of these statuses:
//   idle     — not yet started
//   active   — currently running
//   success  — completed successfully
//   failure  — failed (terminal)
//   canceled — canceled (terminal)
//
// A build is complete when the latest stage status is terminal.
// A build has succeeded when stage = "deploy" AND status = "success".
// A build has failed when any stage status is "failure" or "canceled".
//
// Usage:
//   $result = $service->checkStatus($hook);
//
//   if (! $result->apiCallSucceeded()) {
//       // Show error — $result->errorMessage()
//   } elseif ($result->isPending()) {
//       // Show spinner — $result->currentStage(), $result->currentStageStatus()
//   } elseif ($result->buildSucceeded()) {
//       // Advance pipeline
//   } else {
//       // Build failed — $result->errorMessage() or stage info
//   }
//
// Constructed only via the named static factories — never via new directly.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Services/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Services;

use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class CloudflareBuildStatusResult
{
    // -------------------------------------------------------------------------
    // Terminal stage statuses — build will not progress further.
    // -------------------------------------------------------------------------
    private const TERMINAL_STATUSES = ['success', 'failure', 'canceled'];

    // -------------------------------------------------------------------------
    // The final stage name and success status — used to confirm a full success.
    // -------------------------------------------------------------------------
    private const FINAL_STAGE      = 'deploy';
    private const SUCCESS_STATUS   = 'success';

    // -------------------------------------------------------------------------
    // Constructor — private. Use named static factories below.
    // -------------------------------------------------------------------------
    private function __construct(
        private readonly DeployHook $hook,
        private readonly bool       $apiCallSucceeded,
        private readonly int        $httpStatus,
        private readonly ?string    $currentStage,
        private readonly ?string    $currentStageStatus,
        private readonly ?string    $errorMessage,
    ) {}

    // -------------------------------------------------------------------------
    // Named static factories
    // -------------------------------------------------------------------------

    /**
     * API call succeeded — Cloudflare returned deployment stage data.
     */
    public static function success(
        DeployHook $hook,
        int        $httpStatus,
        string     $currentStage,
        string     $currentStageStatus,
    ): self {
        return new self(
            hook:               $hook,
            apiCallSucceeded:   true,
            httpStatus:         $httpStatus,
            currentStage:       $currentStage,
            currentStageStatus: $currentStageStatus,
            errorMessage:       null,
        );
    }

    /**
     * API call failed — network error, auth failure, or missing data.
     */
    public static function failure(
        DeployHook $hook,
        int        $httpStatus,
        string     $errorMessage,
    ): self {
        return new self(
            hook:               $hook,
            apiCallSucceeded:   false,
            httpStatus:         $httpStatus,
            currentStage:       null,
            currentStageStatus: null,
            errorMessage:       $errorMessage,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * The deploy hook whose build status was checked.
     */
    public function hook(): DeployHook
    {
        return $this->hook;
    }

    /**
     * Whether the HTTP call to the Cloudflare API succeeded.
     * False does not mean the build failed — it means we could not reach
     * Cloudflare or the response was not parseable.
     */
    public function apiCallSucceeded(): bool
    {
        return $this->apiCallSucceeded;
    }

    /**
     * The HTTP status code returned by the Cloudflare API.
     * Zero if a network-level error prevented any response.
     */
    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * The name of the current (latest) deployment stage.
     * e.g. "queued", "initialize", "clone_repo", "build", "deploy".
     * Null if the API call failed.
     */
    public function currentStage(): ?string
    {
        return $this->currentStage;
    }

    /**
     * The status of the current (latest) deployment stage.
     * e.g. "idle", "active", "success", "failure", "canceled".
     * Null if the API call failed.
     */
    public function currentStageStatus(): ?string
    {
        return $this->currentStageStatus;
    }

    /**
     * Human-readable error message.
     * Set on API call failure or guard failures (wrong provider, no build ID).
     * Null when the API call succeeded.
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    // -------------------------------------------------------------------------
    // Convenience state booleans
    // -------------------------------------------------------------------------

    /**
     * Whether the build has reached a terminal state — success, failure,
     * or canceled. A complete build may have succeeded or failed.
     */
    public function isComplete(): bool
    {
        return $this->apiCallSucceeded
            && in_array($this->currentStageStatus, self::TERMINAL_STATUSES, true);
    }

    /**
     * Whether the build completed successfully.
     * True only when the final "deploy" stage reached "success" status.
     */
    public function buildSucceeded(): bool
    {
        return $this->isComplete()
            && $this->currentStage       === self::FINAL_STAGE
            && $this->currentStageStatus === self::SUCCESS_STATUS;
    }

    /**
     * Whether the build completed with a failure or was canceled.
     */
    public function buildFailed(): bool
    {
        return $this->isComplete() && ! $this->buildSucceeded();
    }

    /**
     * Whether the build is still in progress — API call succeeded but the
     * latest stage has not yet reached a terminal status.
     */
    public function isPending(): bool
    {
        return $this->apiCallSucceeded && ! $this->isComplete();
    }
}