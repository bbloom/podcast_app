<?php

// =============================================================================
// Migration: add_intro_outro_template_to_podcast_shows_table
//
// Adds intro_template and outro_template as nullable text columns.
// Used by the Finalize Script Wizard to prepend/append show-specific
// intro and outro text to an episode script.
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
        Schema::table('podcast_shows', function (Blueprint $table) {
            $table->text('intro_template')
                  ->nullable()
                  ->after('rss_link')
                  ->comment('Intro template for this show. Supports {{episode_number}}, {{title}}, {{sponsors}} placeholders.');

            $table->text('outro_template')
                  ->nullable()
                  ->after('intro_template')
                  ->comment('Outro template for this show.');
        });
    }

    public function down(): void
    {
        Schema::table('podcast_shows', function (Blueprint $table) {
            $table->dropColumn(['intro_template', 'outro_template']);
        });
    }
};