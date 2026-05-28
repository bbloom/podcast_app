<?php

// =============================================================================
// GuestEmailDirection
//
// Backs the `direction` column of the `guest_emails` table.
//
// Outbound — email sent by the app to a guest.
// Inbound  — email received from a guest via Postmark's inbound webhook.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Enums/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Enums;

enum GuestEmailDirection: string
{
    case Outbound = 'outbound';
    case Inbound  = 'inbound';
}