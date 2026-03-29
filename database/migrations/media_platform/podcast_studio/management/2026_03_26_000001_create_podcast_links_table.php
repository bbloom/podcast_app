<?php

// =============================================================================
// Migration: create_podcast_links_table
//
// Stores reusable links (e.g. show notes URLs, references) that can be
// attached to one or more podcast episodes via the podcast_link_episode
// pivot table.
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
        Schema::create('podcast_links', function (Blueprint $table) {

            $table->comment('Reusable links that can be attached to one or more podcast episodes.');

            $table->id();

            $table->string('title')
                    ->nullable();

            $table->string('link');

            $table->text('description')
                  ->nullable()
                  ->comment('Optional description.');

            $table->text('comments')
                  ->nullable()
                  ->comment('Internal comments about this link. Not for publishing.');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('Whether this link is active and available for use.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_links');
    }
};