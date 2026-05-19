<?php

// =============================================================================
// Migration: rename_podcast_episodes_to_podcast_episodes_published
//
// Renames the podcast_episodes table to podcast_episodes_published.
//
// PostgreSQL carries all foreign key constraints and indexes over automatically
// on a table rename — no need to drop/recreate constraints on the pivot tables
// (podcast_link_episode, podcast_guest_episode).
//
// Path: database/migrations/media_platform/podcasts/
// Registered in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('podcast_episodes', 'podcast_episodes_published');
    }

    public function down(): void
    {
        Schema::rename('podcast_episodes_published', 'podcast_episodes');
    }
};