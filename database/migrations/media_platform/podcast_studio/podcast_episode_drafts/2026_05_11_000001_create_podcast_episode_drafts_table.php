<?php

// =============================================================================
// Migration: create_podcast_episode_drafts_table
//
// Lightweight planning and drafting workspace for podcast episodes.
// A draft accumulates all the inputs needed for episode creation.
// When finalized, these feed directly into Step3Controller.
//
// Path: database/migrations/media_platform/podcast_studio/podcast_episode_drafts/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums\PodcastEpisodeDraftStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_episode_drafts', function (Blueprint $table) {

            $table->comment('Lightweight planning and drafting workspace for podcast episodes. A draft can be converted into a real podcast_episodes record when ready.');

            $table->id();

            // ------------------------------------------------------------------
            // Ownership & relationships
            // ------------------------------------------------------------------
            $table->foreignId('podcast_show_id')
                  ->constrained('podcast_shows')
                  ->cascadeOnDelete()
                  ->comment('The podcast show this draft belongs to.');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this draft.');

            // ------------------------------------------------------------------
            // Status
            // ------------------------------------------------------------------
            $table->string('status')
                  ->default(PodcastEpisodeDraftStatus::working_on_draft->value)
                  ->comment('Lifecycle status of this draft. Backed by PodcastEpisodeDraftStatus enum.');

            // ------------------------------------------------------------------
            // Planning
            // ------------------------------------------------------------------
            $table->string('title')
                  ->comment('Working title for this draft. Does not need to be final.');

            $table->date('date')
                  ->nullable()
                  ->comment('Tentative recording or release date. No commitment implied.');

            $table->unsignedInteger('episode_number')
                  ->nullable()
                  ->comment('Tentative episode number. May change before conversion.');

            // ------------------------------------------------------------------
            // Draft content
            // ------------------------------------------------------------------
            $table->text('draft')
                  ->nullable()
                  ->comment('The written script or draft text. Will be carried over to the episode draft field upon conversion.');

            // ------------------------------------------------------------------
            // Website
            // ------------------------------------------------------------------
            $table->text('website_content')
                  ->nullable()
                  ->comment('Episode description for the website. Cascades into RSS fields upon episode creation. Refine this during drafting to avoid pressure at creation time.');

            $table->string('website_excerpt')
                  ->nullable()
                  ->comment('Short excerpt for website listings. If left blank, can be derived from website_content.');

            // ------------------------------------------------------------------
            // Guest (lightweight free-text for prospective guests)
            // ------------------------------------------------------------------
            $table->string('guest_notes')
                  ->nullable()
                  ->comment('Optional free-form notes about prospective guests not yet in the podcast_guests table. For confirmed guests, use the podcast_guest_episode_draft pivot.');

            // ------------------------------------------------------------------
            // Notes & external references
            // ------------------------------------------------------------------
            $table->string('comments')
                  ->nullable()
                  ->comment('Status notes, reminders, or general comments about this draft.');

            $table->string('basecamp_url')
                  ->nullable()
                  ->comment('URL to the Basecamp project for this episode, if one exists.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episode_drafts');
    }
};