<?php

// =============================================================================
// Migration: create_podcast_guest_episode_planning_table
//
// Pivot table for the many-to-many relationship between podcast_guests and
// podcast_episodes_planning. A guest can appear on many planning episodes;
// a planning episode can have many guests.
//
// Mirrors the structure of podcast_guest_episode (the published episodes
// pivot), but references podcast_episodes_planning instead.
//
// No surrogate id — composite primary key prevents duplicate attachments.
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
        Schema::create('podcast_guest_episode_planning', function (Blueprint $table) {

            $table->comment(
                'Pivot table joining podcast_guests to podcast_episodes_planning (many-to-many). ' .
                'Mirrors podcast_guest_episode for the planning phase.'
            );

            $table->unsignedBigInteger('podcast_guest_id')
                  ->comment('Foreign key to podcast_guests.id.');

            $table->unsignedBigInteger('podcast_episode_planning_id')
                  ->comment('Foreign key to podcast_episodes_planning.id.');

            // Composite primary key — prevents duplicate guest-episode attachments.
            $table->primary(['podcast_guest_id', 'podcast_episode_planning_id']);

            $table->foreign('podcast_guest_id')
                  ->references('id')
                  ->on('podcast_guests')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_planning_id')
                  ->references('id')
                  ->on('podcast_episodes_planning')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_guest_episode_planning');
    }
};