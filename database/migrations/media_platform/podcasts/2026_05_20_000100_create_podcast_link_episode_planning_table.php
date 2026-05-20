<?php

// =============================================================================
// Migration: create_podcast_link_episode_planning_table
//
// Pivot table joining podcast_links to podcast_episodes_planning (many-to-many).
// Links attached to a planning episode are migrated to podcast_link_episode
// when the episode is handed off to publishing via the PrepareForPublishing
// Wizard.
//
// Mirrors the structure of podcast_link_episode for the planning phase.
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
        Schema::create('podcast_link_episode_planning', function (Blueprint $table) {

            $table->comment(
                'Pivot table joining podcast_links to podcast_episodes_planning (many-to-many). ' .
                'Links are migrated to podcast_link_episode when the episode is published.'
            );

            $table->unsignedBigInteger('podcast_link_id')
                  ->comment('Foreign key to podcast_links.id.');

            $table->unsignedBigInteger('podcast_episode_planning_id')
                  ->comment('Foreign key to podcast_episodes_planning.id.');

            // Composite primary key prevents duplicate link-episode attachments.
            $table->primary(['podcast_link_id', 'podcast_episode_planning_id']);

            $table->foreign('podcast_link_id')
                  ->references('id')
                  ->on('podcast_links')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_planning_id')
                  ->references('id')
                  ->on('podcast_episodes_planning')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_link_episode_planning');
    }
};