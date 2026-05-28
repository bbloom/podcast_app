<?php

// =============================================================================
// Migration: create_guest_emails_table
//
// Stores all email correspondence between the app and podcast guests.
// Both outbound (app → guest) and inbound (guest → app) emails are recorded.
//
// Thread correlation is handled via message_id and in_reply_to:
//   - Every outbound email stores its RFC 2822 Message-ID (without angle brackets).
//   - Inbound replies store In-Reply-To, which matches a prior outbound message_id.
//
// Path: database/migrations/media_platform/podcasts/
// Registered in AppServiceProvider::boot() via loadMigrationsFrom() —
// the 'migrations/media_platform/podcasts' path is already registered.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\Podcasts\Guests\Enums\GuestEmailDirection;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_emails', function (Blueprint $table) {

            $table->comment(
                'Inbound and outbound email correspondence between the app and podcast guests. ' .
                'Thread correlation uses message_id (outbound) matched against in_reply_to (inbound).'
            );

            $table->id();

            // ------------------------------------------------------------------
            // Ownership
            // ------------------------------------------------------------------

            $table->foreignId('podcast_guest_id')
                  ->constrained('podcast_guests')
                  ->cascadeOnDelete()
                  ->comment('The guest this email belongs to.');

            // ------------------------------------------------------------------
            // Direction
            // ------------------------------------------------------------------

            $table->string('direction')
                  ->default(GuestEmailDirection::Outbound->value)
                  ->comment('outbound = sent by the app to the guest; inbound = received from the guest via Postmark webhook. Backed by GuestEmailDirection enum.');

            // ------------------------------------------------------------------
            // Content
            // ------------------------------------------------------------------

            $table->string('subject')
                  ->comment('Email subject line.');

            $table->text('body_stripped')
                  ->comment(
                      'For inbound: Postmark\'s StrippedTextReply — only what the guest wrote, quoted history removed. ' .
                      'For outbound: the full composed message body.'
                  );

            $table->text('body_full')
                  ->comment(
                      'For inbound: Postmark\'s TextBody — full text including quoted history. ' .
                      'Retained as a fallback if StrippedTextReply misfires on an unusual email client. ' .
                      'For outbound: same value as body_stripped.'
                  );

            // ------------------------------------------------------------------
            // Thread correlation
            // ------------------------------------------------------------------

            $table->string('message_id')
                  ->unique()
                  ->comment('RFC 2822 Message-ID of this email, stored without angle brackets. Used to correlate inbound replies via in_reply_to.');

            $table->string('in_reply_to')
                  ->nullable()
                  ->comment('In-Reply-To header value from an inbound email, stored without angle brackets. Matches the message_id of a prior outbound row. Null for outbound rows and first-contact inbound messages.');

            // ------------------------------------------------------------------
            // Timestamps
            // ------------------------------------------------------------------

            $table->timestamp('sent_at')
                  ->nullable()
                  ->comment('When the outbound email was sent by the app. Null for inbound rows.');

            $table->timestamp('received_at')
                  ->nullable()
                  ->comment('When the inbound email was received by Postmark. Null for outbound rows.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_emails');
    }
};