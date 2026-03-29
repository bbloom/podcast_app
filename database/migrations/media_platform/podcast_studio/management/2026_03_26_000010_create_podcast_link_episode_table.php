<?php

// =============================================================================
// Migration: create_podcast_link_episode_table
//
// Pivot table for the many-to-many relationship between podcast_links and
// podcast_episodes. A link can be attached to many episodes, and an episode
// can have many links.
//
// No surrogate id — composite primary key prevents duplicate attachments.
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
        Schema::create('podcast_link_episode', function (Blueprint $table) {

            $table->comment('Pivot table joining podcast_links to podcast_episodes (many-to-many).');

            $table->unsignedBigInteger('podcast_link_id')
                  ->comment('Foreign key to podcast_links.id.');

            $table->unsignedBigInteger('podcast_episode_id')
                  ->comment('Foreign key to podcast_episodes.id.');

            // Composite primary key — prevents duplicate link-episode attachments.
            $table->primary(['podcast_link_id', 'podcast_episode_id']);

            $table->foreign('podcast_link_id')
                  ->references('id')
                  ->on('podcast_links')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_id')
                  ->references('id')
                  ->on('podcast_episodes')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_link_episode');
    }
};