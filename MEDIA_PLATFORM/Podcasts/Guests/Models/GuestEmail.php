<?php

// =============================================================================
// GuestEmail
//
// Represents a single email record in the guest_emails table.
// Covers both outbound (app → guest) and inbound (guest → app) messages.
//
// Thread correlation:
//   - Outbound rows have a unique message_id; in_reply_to is null.
//   - Inbound rows set in_reply_to to the message_id of a prior outbound row.
//
// Path: MEDIA_PLATFORM/Podcasts/Guests/Models/
// =============================================================================

namespace MediaPlatform\Podcasts\Guests\Models;

use Database\Factories\Media_platform\Podcasts\Guests\GuestEmailFactory;
use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestEmail extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'guest_emails';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'podcast_guest_id',
        'direction',
        'subject',
        'body_stripped',
        'body_full',
        'message_id',
        'in_reply_to',
        'sent_at',
        'received_at',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'direction'   => GuestEmailDirection::class,
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): GuestEmailFactory
    {
        return GuestEmailFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The podcast guest this email belongs to.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(PodcastGuest::class, 'podcast_guest_id');
    }
}