<?php

// =============================================================================
// Migration: create_podcast_shows_table
//
// Stores podcast show metadata. A show maps to the RSS <channel> element.
// Each show belongs to a user and has one RSS feed.
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
        Schema::create('podcast_shows', function (Blueprint $table) {

            $table->comment('Podcast shows. Each show maps to the RSS <channel> element and belongs to a user.');

            $table->id();

            // ------------------------------------------------------------------
            // Ownership
            // ------------------------------------------------------------------

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this podcast show.');

            // ------------------------------------------------------------------
            // Core
            // ------------------------------------------------------------------

            $table->string('title')
                  ->comment('The podcast show title.');

            $table->string('slug')
                  ->unique()
                  ->comment('URL-friendly identifier for the show. Generated via makeSlug().');

            $table->text('description')
                  ->comment('The podcast show description.');

            $table->string('rss_link')
                  ->nullable()
                  ->comment('The URL of this show\'s RSS feed.');

            // ------------------------------------------------------------------
            // iTunes / Apple Podcasts
            // ------------------------------------------------------------------

            $table->string('itunes_image')
                  ->nullable()
                  ->comment('Podcast cover art URL. Recommended size: 3000x3000px.');

            $table->string('itunes_language')
                  ->default('en')
                  ->comment('Language of the podcast, e.g. "en". ISO 639 language code.');

            $table->string('itunes_category_primary')
                  ->nullable()
                  ->comment('Primary iTunes category, e.g. "Technology".');

            $table->string('itunes_category_secondary')
                  ->nullable()
                  ->comment('Optional secondary iTunes category.');

            $table->boolean('itunes_explicit')
                  ->default(false)
                  ->comment('Whether the show contains explicit content.');

            $table->string('itunes_author')
                  ->nullable()
                  ->comment('The author name shown in podcast directories.');

            $table->string('itunes_link')
                  ->nullable()
                  ->comment('The podcast website URL used in the iTunes tag.');

            $table->string('itunes_email')
                  ->nullable()
                  ->comment('Contact email address for the podcast owner.');

            $table->string('itunes_name')
                  ->nullable()
                  ->comment('The podcast owner\'s full name.');

            $table->string('itunes_title')
                  ->nullable()
                  ->comment('The iTunes-specific title tag for the show.');

            $table->string('itunes_type')
                  ->nullable()
                  ->comment('Podcast type: "episodic" or "serial".');

            $table->string('itunes_copyright')
                  ->nullable()
                  ->comment('Copyright notice for the podcast.');

            $table->string('itunes_new_feed_url')
                  ->nullable()
                  ->comment('Used to notify podcast directories of a feed URL change.');

            $table->boolean('itunes_block')
                  ->default(false)
                  ->comment('If true, prevents the show from appearing in Apple Podcasts.');

            $table->boolean('itunes_complete')
                  ->default(false)
                  ->comment('If true, signals to Apple Podcasts that no new episodes will be published.');

            $table->text('itunes_summary')
                  ->nullable()
                  ->comment('iTunes summary tag. Plain text description of the podcast.');

            $table->string('itunes_subtitle')
                  ->nullable()
                  ->comment('iTunes subtitle tag. A brief tagline for the show.');

            $table->text('itunes_content_encoded')
                  ->nullable()
                  ->comment('HTML content for the <content:encoded> tag in the RSS feed.');

            // ------------------------------------------------------------------
            // Spotify
            // ------------------------------------------------------------------

            $table->unsignedInteger('spotify_limit')
                  ->default(0)
                  ->comment('Maximum number of episodes Spotify should index. 0 means no limit.');

            $table->string('spotify_country_of_origin')
                  ->default('global')
                  ->comment('Country of origin for Spotify distribution.');

            // ------------------------------------------------------------------
            // Website
            // ------------------------------------------------------------------

            $table->text('website_content')
                  ->nullable()
                  ->comment('Full HTML content for the show\'s page on the website.');

            $table->string('website_excerpt')
                  ->nullable()
                  ->comment('Short excerpt shown in show listings on the website.');

            $table->string('website_meta_description')
                  ->nullable()
                  ->comment('Meta description tag for SEO on the show\'s website page.');

            $table->string('website_featured_image')
                  ->nullable()
                  ->comment('Featured image URL for the show\'s website page.');

            $table->date('website_publish_on')
                  ->default(now())
                  ->comment('The date the show page should be published on the website.');

            $table->boolean('website_enabled')
                  ->default(false)
                  ->comment('Whether this show is publicly visible on the website.');

            // ------------------------------------------------------------------
            // Storage
            // ------------------------------------------------------------------

            $table->text('storage_artwork_url')
                  ->nullable()
                  ->comment('Base URL of the storage location for this show\'s artwork files.');

            $table->text('storage_video_files_url')
                  ->nullable()
                  ->comment('Base URL of the storage location for this show\'s video files.');

            $table->text('storage_audio_files_url')
                  ->nullable()
                  ->comment('Base URL of the storage location for this show\'s audio files.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_shows');
    }
};