<?php

// =============================================================================
// Migration: create_podcast_guests_table
//
// Stores guest profiles for podcast episodes. A guest can appear on many
// episodes; an episode can have many guests (many-to-many via
// podcast_guest_episode pivot table).
//
// Path: database/migrations/media_platform/podcast_studio/management/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_guests', function (Blueprint $table) {

            $table->comment('Guest profiles for podcast episodes. A guest may appear on many episodes via the podcast_guest_episode pivot table.');

            $table->id();

            $table->string('full_name')
                  ->unique()
                  ->comment('First and last name of the guest.');

            $table->string('image_url')
                  ->nullable()
                  ->comment('URL to the guest\'s full-size profile image.');

            $table->string('image_thumbnail_url')
                  ->nullable()
                  ->comment('URL to the guest\'s thumbnail image.');

            $table->text('profile_full')
                  ->comment('Full biography or profile text.');

            $table->string('profile_short')
                  ->nullable()
                  ->comment('Short one-line profile or tagline.');

            $table->string('link_to_guest_website')
                  ->nullable()
                  ->comment('URL to the guest\'s website.');

            $table->string('email_address')
                  ->comment('Guest\'s email address. Not related to the application\'s users table.');

            $table->text('internal_comment')
                  ->nullable()
                  ->comment('Internal notes about this guest. Not for publishing.');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('Whether this guest record is active and visible.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_guests');
    }
};