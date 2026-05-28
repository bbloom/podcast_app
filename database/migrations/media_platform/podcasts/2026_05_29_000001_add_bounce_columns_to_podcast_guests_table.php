<?php

// =============================================================================
// Migration: add_bounce_columns_to_podcast_guests_table
//
// Adds email bounce tracking columns to podcast_guests.
//
// email_bounced    — true if a HardBounce was received for this guest's address.
//                    SoftBounce and SpamComplaint do not set this flag.
// email_bounced_at — when the HardBounce was recorded. Null if never bounced.
//
// Bounce events of all types are also stored in guest_emails for full history.
// See GuestEmailDirection enum for the full set of direction values.
//
// Path: database/migrations/media_platform/podcasts/
// Registered in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('podcast_guests', function (Blueprint $table) {

            $table->boolean('email_bounced')
                  ->default(false)
                  ->after('enabled')
                  ->comment('True if a hard bounce was received for this guest\'s email address. Soft bounces and spam complaints do not set this flag.');

            $table->timestamp('email_bounced_at')
                  ->nullable()
                  ->after('email_bounced')
                  ->comment('When the hard bounce was recorded. Null if the address has never hard-bounced.');
        });
    }

    public function down(): void
    {
        Schema::table('podcast_guests', function (Blueprint $table) {
            $table->dropColumn(['email_bounced', 'email_bounced_at']);
        });
    }
};