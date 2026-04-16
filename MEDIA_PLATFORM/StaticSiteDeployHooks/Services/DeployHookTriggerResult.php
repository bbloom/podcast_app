<?php

// =============================================================================
// DeployHookTriggerResult
//
// Immutable value object returned by DeployHookTriggerService::trigger().
//
// Carries everything the UI needs to render the results page — success or
// failure, the build ID returned by the provider, whether a build was already
// in progress, the raw HTTP status, and a human-readable error message on
// failure.
//
// Usage:
//   $result = $service->trigger($hook);
//
//   if ($result->succeeded()) {
//       // Show success — optionally display $result->buildId()
//   } else {
//       // Show failure — display $result->errorMessage()
//   }
//
// Constructed only via the named static factories — never via new directly.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Services/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Services;

use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;

class DeployHookTriggerResult
{
    // -------------------------------------------------------------------------
    // Constructor — private. Use named static factories below.
    // -------------------------------------------------------------------------

    private function __construct(
        private readonly DeployHook $hook,
        private readonly bool       $succeeded,
        private readonly int        $httpStatus,
        private readonly ?string    $buildId,
        private readonly bool       $alreadyExists,
        private readonly ?string    $errorMessage,
    ) {}

    // -------------------------------------------------------------------------
    // Named static factories
    // -------------------------------------------------------------------------

    /**
     * Create a successful trigger result.
     */
    public static function success(
        DeployHook $hook,
        int        $httpStatus,
        ?string    $buildId      = null,
        bool       $alreadyExists = false,
    ): self {
        return new self(
            hook:          $hook,
            succeeded:     true,
            httpStatus:    $httpStatus,
            buildId:       $buildId,
            alreadyExists: $alreadyExists,
            errorMessage:  null,
        );
    }

    /**
     * Create a failed trigger result.
     */
    public static function failure(
        DeployHook $hook,
        int        $httpStatus,
        string     $errorMessage,
    ): self {
        return new self(
            hook:          $hook,
            succeeded:     false,
            httpStatus:    $httpStatus,
            buildId:       null,
            alreadyExists: false,
            errorMessage:  $errorMessage,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * The deploy hook that was triggered.
     */
    public function hook(): DeployHook
    {
        return $this->hook;
    }

    /**
     * Whether the trigger was successful.
     */
    public function succeeded(): bool
    {
        return $this->succeeded;
    }

    /**
     * The HTTP status code returned by the provider.
     */
    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * The build identifier returned by the provider.
     * Cloudflare returns a UUID. Netlify and Vercel return null.
     * Null on failure.
     */
    public function buildId(): ?string
    {
        return $this->buildId;
    }

    /**
     * Whether a build was already queued or initialising when this hook fired.
     * Cloudflare deduplicates rapid successive triggers — if true, no new
     * build was created, the existing one continues.
     */
    public function alreadyExists(): bool
    {
        return $this->alreadyExists;
    }

    /**
     * Human-readable error message. Null on success.
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}