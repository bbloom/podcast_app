<?php

// =============================================================================
// Migration: create_podcast_guest_episode_draft_table
//
// Pivot table for the many-to-many relationship between podcast_guests and
// podcast_episode_drafts. A guest can be planned for many drafts; a draft
// can have many prospective guests.
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
        Schema::create('podcast_guest_episode_draft', function (Blueprint $table) {

            $table->comment('Pivot table joining podcast_guests to podcast_episode_drafts (many-to-many).');

            $table->unsignedBigInteger('podcast_guest_id')
                  ->comment('Foreign key to podcast_guests.id.');

            $table->unsignedBigInteger('podcast_episode_draft_id')
                  ->comment('Foreign key to podcast_episode_drafts.id.');

            $table->primary(['podcast_guest_id', 'podcast_episode_draft_id'], 'guest_draft_primary');

            $table->foreign('podcast_guest_id')
                  ->references('id')
                  ->on('podcast_guests')
                  ->cascadeOnDelete();

            $table->foreign('podcast_episode_draft_id')
                  ->references('id')
                  ->on('podcast_episode_drafts')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_guest_episode_draft');
    }
};