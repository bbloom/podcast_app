<?php

// =============================================================================
// Migration: create_podcast_guest_episode_table
//
// Pivot table for the many-to-many relationship between podcast_guests and
// podcast_episodes. A guest can appear on many episodes; an episode can
// have many guests.
//
// No surrogate id — composite primary key prevents duplicate attachments.
//
// Path: database/migrations/media_platform/podcasts/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_guest_episode', function (Blueprint $table) {

            $table->comment('Pivot table joining podcast_guests to podcast_episodes (many-to-many).');

            $table->unsignedBigInteger('podcast_guest_id')
                  ->comment('Foreign key to podcast_guests.id.');

            $table->unsignedBigInteger('podcast_episode_id')
                  ->comment('Foreign key to podcast_episodes.id.');

            // Composite primary key — prevents duplicate guest-episode attachments.
            $table->primary(['podcast_guest_id', 'podcast_episode_id']);

            $table->foreign('podcast_guest_id')
                  ->references('id')
                  ->on('podcast_guests')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_id')
                  ->references('id')
                  ->on('podcast_episodes')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_guest_episode');
    }
};