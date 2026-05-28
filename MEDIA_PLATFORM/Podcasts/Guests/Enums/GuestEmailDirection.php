<?php

// =============================================================================
// GuestEmailDirection
//
// Backs the `direction` column of the `guest_emails` table.
//
// Outbound      — email sent by the app to a guest.
// Inbound       — email received from a guest via Postmark's inbound webhook.
// HardBounce    — permanent delivery failure. Guest record is flagged.
// SoftBounce    — temporary delivery failure. Logged only.
// SpamComplaint — guest marked the email as spam. Logged only.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Enums/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Enums;

enum GuestEmailDirection: string
{
    case Outbound       = 'outbound';
    case Inbound        = 'inbound';
    case HardBounce     = 'hard_bounce';
    case SoftBounce     = 'soft_bounce';
    case SpamComplaint  = 'spam_complaint';
}