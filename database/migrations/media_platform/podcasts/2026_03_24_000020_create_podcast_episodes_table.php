<?php

// =============================================================================
// Migration: create_podcast_episodes_table
//
// Stores podcast episode metadata. An episode maps to an RSS <item> element.
// Each episode belongs to a podcast show and a user.
//
// Path: database/migrations/media_platform/podcast_studio/management/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_episodes', function (Blueprint $table) {

            $table->comment('Podcast episodes. Each episode maps to an RSS <item> element and belongs to a podcast show.');

            $table->id();

            // ------------------------------------------------------------------
            // Ownership & relationships
            // ------------------------------------------------------------------
            $table->foreignId('podcast_show_id')
                  ->constrained('podcast_shows')
                  ->cascadeOnDelete()
                  ->comment('The podcast show this episode belongs to.');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this episode.');

            // ------------------------------------------------------------------
            // Status
            //
            // see: MEDIA_PLATFORM/PodcastStudio/Management/Enums/PodcastEpisodeStatus.php
            // ------------------------------------------------------------------
            $table->string('status')
                  ->default(PodcastEpisodeStatus::created->value)
                  ->comment('The current status of this episode. Backed by the PodcastEpisodeStatus enum.');

            // ------------------------------------------------------------------
            // Core
            // ------------------------------------------------------------------
            $table->string('title')
                  ->comment('The episode title.');

            $table->string('slug')
                  ->unique()
                  ->comment('URL-friendly identifier for the episode. Generated via makeSlug().');

            $table->date('scheduled_date')
                  ->nullable()
                  ->comment('The date this episode is scheduled to be recorded or released.');

            $table->text('draft')
                  ->nullable()
                  ->comment('The written draft or script for this episode. Used as the basis for transcripts and LLM processing.');

            $table->string('raw_input_audio_filename')
                  ->nullable()
                  ->comment('Filename of the raw unedited audio file before post-production.');


            // ------------------------------------------------------------------
            // Auphonic post-production
            //
            // Tracks the Auphonic production UUID assigned when a production is
            // submitted to Auphonic for audio processing. Cleared after clean-up.
            // ------------------------------------------------------------------
            $table->string('auphonic_production_uuid')
                  ->nullable()
                  ->comment('UUID assigned by Auphonic when a production is created. Cleared after post-production clean-up.');

            
            
            // ------------------------------------------------------------------
            // iTunes / Apple Podcasts
            // ------------------------------------------------------------------
            $table->string('itunes_title_tag')
                  ->nullable()
                  ->comment('iTunes-specific title tag for the episode, if different from the main title.');

            $table->string('itunes_enclosure_url')
                  ->nullable()
                  ->comment('URL of the episode audio file. Maps to the RSS <enclosure url=""> attribute.');

            $table->string('itunes_enclosure_length')
                  ->nullable()
                  ->comment('File size of the audio file in bytes. Maps to the RSS <enclosure length=""> attribute.');

            $table->string('itunes_enclosure_type')
                  ->nullable()
                  ->comment('MIME type of the audio file, e.g. "audio/mpeg". Maps to the RSS <enclosure type=""> attribute.');

            $table->string('itunes_guid')
                  ->nullable()
                  ->comment('Globally unique identifier for this episode. Used by podcast apps to track episodes.');

            $table->datetime('itunes_pubdate')
                  ->nullable()
                  ->comment('The publication date and time of this episode in the RSS feed.');

            $table->text('itunes_description')
                  ->nullable()
                  ->comment('Plain text description of the episode for the RSS feed.');

            $table->string('itunes_duration')
                  ->nullable()
                  ->comment('Duration of the episode, e.g. "45:30" or "2730". Maps to <itunes:duration>.');

            $table->string('itunes_link')
                  ->nullable()
                  ->comment('URL of the episode\'s page on the podcast website.');

            $table->string('itunes_image')
                  ->nullable()
                  ->comment('Episode-specific cover art URL. Overrides the show artwork if set.');

            $table->boolean('itunes_explicit')
                  ->default(false)
                  ->comment('Whether this episode contains explicit content.');

            $table->string('itunes_itunestitle_tag')
                  ->nullable()
                  ->comment('Secondary iTunes title tag for the episode.');

            $table->unsignedInteger('itunes_episode')
                  ->default(0)
                  ->comment('Episode number within the season. Maps to <itunes:episode>.');

            $table->unsignedInteger('itunes_season')
                  ->default(0)
                  ->comment('Season number this episode belongs to. Maps to <itunes:season>.');

            $table->string('itunes_episode_type')
                  ->default('full')
                  ->comment('Episode type: "full", "trailer", or "bonus". Maps to <itunes:episodeType>.');

            $table->boolean('itunes_block')
                  ->default(false)
                  ->comment('If true, prevents this episode from appearing in Apple Podcasts.');

            $table->text('itunes_summary')
                  ->nullable()
                  ->comment('iTunes summary for the episode. Plain text.');

            $table->string('itunes_subtitle')
                  ->nullable()
                  ->comment('iTunes subtitle tag. A brief tagline for the episode.');

            $table->text('itunes_content_encoded')
                  ->nullable()
                  ->comment('HTML content for the <content:encoded> tag. Rich show notes for the episode.');

            // ------------------------------------------------------------------
            // RSS feed
            // ------------------------------------------------------------------
            $table->boolean('rss_feed_enabled')
                  ->default(false)
                  ->comment('Master toggle for whether this episode appears in the RSS feed.');

            // ------------------------------------------------------------------
            // Website
            // ------------------------------------------------------------------
            $table->text('website_content')
                  ->nullable()
                  ->comment('Full HTML content for the episode\'s page on the website.');

            $table->string('website_excerpt')
                  ->nullable()
                  ->comment('Short excerpt shown in episode listings on the website.');

            $table->string('website_meta_description')
                  ->nullable()
                  ->comment('Meta description tag for SEO on the episode\'s website page.');

            $table->text('website_episode_notes')
                  ->nullable()
                  ->comment('Additional show notes displayed on the episode\'s website page.');

            $table->text('website_attribution')
                  ->nullable()
                  ->comment('Attribution text for music, guests, or other credited content.');

            $table->string('website_featured_image')
                  ->nullable()
                  ->comment('Featured image URL for the episode\'s website page.');

            $table->date('website_publish_on')
                  ->default(now())
                  ->comment('The date the episode page should be published on the website.');

            $table->boolean('website_enabled')
                  ->default(false)
                  ->comment('Whether this episode is publicly visible on the website.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episodes');
    }
};