<?php

// =============================================================================
// Migration: create_podcast_episodes_planning_table
//
// The home for every podcast episode during its creative, planning, and
// assembly life. Replaces the retired `podcast_episode_drafts` table.
//
// Records are HARD DELETED (no soft deletes) when an episode is handed off
// to the Publishing phase and a corresponding `podcast_episodes_published`
// record is created by the Prepare for Publishing Wizard.
//
// Statuses are tracked via the PodcastEpisodePlanningStatus enum.
// Statuses can move BACKWARDS — the app does not enforce forward-only
// progression. Data is never cleared on a backwards status move.
//
// Guest relationships are managed via the podcast_guest_episode_planning
// pivot table — see the accompanying migration.
//
// Path: database/migrations/media_platform/podcasts/
// Registered in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_episodes_planning', function (Blueprint $table) {

            $table->comment(
                'Planning workspace for podcast episodes. ' .
                'Every episode begins here and lives here until it is handed off to the Publishing phase. ' .
                'Records are hard-deleted on publishing — no soft deletes.'
            );

            $table->id();

            // ------------------------------------------------------------------
            // Ownership & relationships
            // ------------------------------------------------------------------

            $table->foreignId('podcast_show_id')
                  ->constrained('podcast_shows')
                  ->cascadeOnDelete()
                  ->comment('The podcast show this planning episode belongs to.');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this planning episode.');

            // ------------------------------------------------------------------
            // Status
            //
            // Backed by PodcastEpisodePlanningStatus enum.
            // Statuses are set automatically by wizards or manually by the user.
            // Backward movement is permitted — data is never cleared on rollback.
            // ------------------------------------------------------------------

            $table->string('status')
                  ->default(PodcastEpisodePlanningStatus::new_episode_created->value)
                  ->comment(
                      'Current planning status. Backed by PodcastEpisodePlanningStatus enum. ' .
                      'new-episode-created → working-on-theme → writing-script → ' .
                      'ready-to-finalize-the-script → ready-to-record → ' .
                      'raw-audio-needs-editing → ready-for-publishing.'
                  );

            // ------------------------------------------------------------------
            // Core identity
            // ------------------------------------------------------------------

            $table->string('title')
                  ->comment('The episode title.');

            $table->unsignedInteger('episode_number')
                  ->nullable()
                  ->comment('Episode number within the show.');

            $table->date('scheduled_date')
                  ->nullable()
                  ->comment('The date this episode is scheduled to be recorded or released.');

            // ------------------------------------------------------------------
            // Creative content
            // ------------------------------------------------------------------

            $table->text('notes')
                  ->nullable()
                  ->comment('Free-form notes about this episode. Not for publishing.');

            $table->text('theme')
                  ->nullable()
                  ->comment('Episode theme or high-level topic notes. Editable via the EditThemeField editor.');

            $table->text('script')
                  ->nullable()
                  ->comment(
                      'The full episode script. Editable via the EditScriptField editor. ' .
                      'Finalised (with intro/outro prepended/appended) by the Finalize Script Wizard. ' .
                      'Locked to ready-to-record status on wizard completion.'
                  );

            // ------------------------------------------------------------------
            // Website content
            //
            // Written during the Planning phase and carried over to
            // podcast_episodes_published by the Prepare for Publishing Wizard.
            // ------------------------------------------------------------------

            $table->text('website_content')
                  ->nullable()
                  ->comment('Full episode page content for the website. HTML or Markdown.');

            $table->text('website_excerpt')
                  ->nullable()
                  ->comment('Short excerpt used in episode listings and meta descriptions on the website.');

            // ------------------------------------------------------------------
            // Timestamps
            // ------------------------------------------------------------------

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episodes_planning');
    }
};