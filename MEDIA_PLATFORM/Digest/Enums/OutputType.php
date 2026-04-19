<?php

namespace MediaPlatform\Digest\Enums;

/**
 * OutputType — the delivery mechanism for a digest.
 *
 * Used in the `lists.output_type` column and branched on in PublishDigest.
 * The lists table stores this as a plain string column — the PHP enum is the
 * sole authority on valid values. No MySQL enum constraint exists.
 *
 * ADDING A NEW TYPE
 * ─────────────────
 * 1. Add a case here.
 * 2. Update label(), requiresDestination(), and requiresDeployHooks().
 * 3. Create a delivery strategy class implementing DigestDeliveryStrategy.
 * 4. Register the strategy in DeliveryStrategyResolver::resolve().
 * 5. Update ListWizardController validation (step3Submit) to accept the new value.
 * 6. Update the Blade radio buttons in views/lists/wizard-step3.blade.php.
 * 7. Add wizard steps if the new type needs its own UI flow.
 */
enum OutputType: string
{
    // -------------------------------------------------------------------------
    // The digest is rendered as a full HTML page and uploaded to a web server
    // via SFTP. If notify_by_email is true, a notification email is sent after
    // upload with a link to the published page.
    // -------------------------------------------------------------------------
    case Webpage = 'webpage';

    // -------------------------------------------------------------------------
    // The digest HTML is sent directly as the email body. No file is created
    // and no SFTP connection is made.
    // -------------------------------------------------------------------------
    case Email = 'email';

    // -------------------------------------------------------------------------
    // The digest data is persisted to the published_digests table as structured
    // JSON. Deploy hooks are fired to trigger a static site rebuild. The static
    // site generator fetches the data via the API during its build.
    // -------------------------------------------------------------------------
    case StaticSite = 'static_site';

    // -------------------------------------------------------------------------
    // Human-readable label for display in the UI.
    // -------------------------------------------------------------------------
    public function label(): string
    {
        return match ($this) {
            self::Webpage    => 'Web Page (SFTP)',
            self::Email      => 'Email',
            self::StaticSite => 'Static Site',
        };
    }

    // -------------------------------------------------------------------------
    // Whether this output type requires an OutputDestination record (SFTP).
    // Email and StaticSite do not — Email delivers directly, StaticSite uses
    // deploy hooks and the API instead.
    // -------------------------------------------------------------------------
    public function requiresDestination(): bool
    {
        return match ($this) {
            self::Webpage    => true,
            self::Email      => false,
            self::StaticSite => false,
        };
    }

    // -------------------------------------------------------------------------
    // Whether this output type uses deploy hooks to trigger static site builds.
    // -------------------------------------------------------------------------
    public function requiresDeployHooks(): bool
    {
        return match ($this) {
            self::StaticSite => true,
            default          => false,
        };
    }
}