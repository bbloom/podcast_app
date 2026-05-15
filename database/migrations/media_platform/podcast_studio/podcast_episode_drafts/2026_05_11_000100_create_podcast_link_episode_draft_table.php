<?php

// =============================================================================
// Migration: create_podcast_link_episode_draft_table
//
// Pivot table: podcast_links <-> podcast_episode_drafts (many-to-many).
// Links attached to a draft are migrated to the podcast_link_episode pivot
// upon conversion to a real episode.
//
// Path: database/migrations/media_platform/podcast_studio/podcast_episode_drafts/
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_link_episode_draft', function (Blueprint $table) {

            $table->comment('Pivot table joining podcast_links to podcast_episode_drafts (many-to-many). Links migrate to the episode upon conversion.');

            $table->unsignedBigInteger('podcast_link_id')
                  ->comment('Foreign key to podcast_links.id.');

            $table->unsignedBigInteger('podcast_episode_draft_id')
                  ->comment('Foreign key to podcast_episode_drafts.id.');

            $table->primary(['podcast_link_id', 'podcast_episode_draft_id'], 'link_draft_primary');

            $table->foreign('podcast_link_id')
                  ->references('id')
                  ->on('podcast_links')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_draft_id')
                  ->references('id')
                  ->on('podcast_episode_drafts')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_link_episode_draft');
    }
};