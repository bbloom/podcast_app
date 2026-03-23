<?php

namespace MediaPlatform\Enums;

/**
 * OutputType — the delivery mechanism for a digest.
 *
 * Used in the `lists.output_type` column and branched on in PublishDigest.
 *
 * ADDING A NEW TYPE
 * ─────────────────
 * 1. Add a case here.
 * 2. Write a migration to extend the enum on the lists table.
 * 3. Add delivery logic to PublishDigest::publish().
 * 4. Add wizard steps to OutputDestinationWizardController if credentials are needed.
 * 5. Update ListWizardController validation (step3Submit) to accept the new value.
 * 6. Update the Blade radio buttons in views/lists/wizard-step3.blade.php.
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
    // The digest is published as a WordPress post via the REST API.
    // Authentication uses WordPress Application Passwords (Basic Auth).
    // The OutputDestination holds the WP site URL, username, and app password.
    // -------------------------------------------------------------------------
    case Wordpress = 'wordpress';

    // -------------------------------------------------------------------------
    // Human-readable label for display in the UI.
    // -------------------------------------------------------------------------
    public function label(): string
    {
        return match ($this) {
            self::Webpage   => 'Web Page (SFTP)',
            self::Email     => 'Email',
            self::Wordpress => 'WordPress',
        };
    }

    // -------------------------------------------------------------------------
    // Whether this output type requires an OutputDestination record.
    // Email does not — it delivers directly to the list owner's email address.
    // -------------------------------------------------------------------------
    public function requiresDestination(): bool
    {
        return match ($this) {
            self::Webpage   => true,
            self::Wordpress => true,
            self::Email     => false,
        };
    }
}