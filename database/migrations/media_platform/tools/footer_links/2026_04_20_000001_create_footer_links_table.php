<?php

// =============================================================================
// Migration: create_footer_links_table
//
// Stores footer links for podcast show front-end websites. Each link belongs
// to exactly one podcast show and one user. Displayed in the footer of the
// Astro-based static site, ordered by link_order ascending.
//
// Path: database/migrations/media_platform/tools/footer_links/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {

            $table->comment('Footer links displayed on podcast show front-end websites. Each link belongs to exactly one podcast show.');

            $table->id();

            $table->foreignId('podcast_show_id')
                  ->constrained('podcast_shows')
                  ->cascadeOnDelete()
                  ->comment('The podcast show this footer link belongs to.');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this footer link.');

            $table->string('link_name')
                  ->comment('Display text for the footer link.');

            $table->string('link_url')
                  ->comment('The URL the footer link points to.');

            $table->unsignedInteger('link_order')
                  ->default(0)
                  ->comment('Sort order for display. Lower numbers appear first.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};