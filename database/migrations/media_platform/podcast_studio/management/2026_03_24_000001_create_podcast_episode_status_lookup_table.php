<?php

// =============================================================================
// Migration: create_podcast_episode_status_lookup_table
//
// Lookup table for podcast episode statuses (e.g. Draft, Scheduled,
// Published). Referenced by podcast_episodes.podcast_episode_status_lookup_id.
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
        Schema::create('podcast_episode_status_lookup', function (Blueprint $table) {

            $table->comment('Lookup table of possible statuses for a podcast episode, e.g. Draft, Scheduled, Published.');

            $table->id();

            $table->string('title')
                  ->unique()
                  ->comment('The status label, e.g. "Draft", "Scheduled", "Published".');

            $table->string('description')
                  ->comment('Longer description of what this status means.');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('Whether this status is available for selection.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episode_status_lookup');
    }
};