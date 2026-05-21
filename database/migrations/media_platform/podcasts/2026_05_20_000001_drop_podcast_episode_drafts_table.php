<?php

// =============================================================================
// Migration: drop_podcast_episode_drafts_table
//
// The podcast_episode_drafts table was retired in Phase 1 of the podcast
// app refactor (Version 1 → Version 2). It has been replaced by
// podcast_episodes_planning (created in Phase 3).
//
// There is no live data in this table. It is safe to drop.
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
        Schema::dropIfExists('podcast_episode_drafts');
    }

    public function down(): void
    {
        // This table is intentionally not recreated on rollback.
        // It was retired in Phase 1 and contains no live data.
        // If you need to recover the schema, refer to the original
        // create_podcast_episode_drafts_table migration.
    }
};