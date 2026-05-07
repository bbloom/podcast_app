<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the videos table.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->comment('Videos for publication to YouTube.');

            $table->id();

            // ------------------------------------------------------------------
            // Ownership
            // ------------------------------------------------------------------
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The user who owns this video.');

            // ------------------------------------------------------------------
            // General
            // ------------------------------------------------------------------
            $table->string('title')
                ->comment('The working title of the video.');

            $table->string('slug')
                ->unique()
                ->comment('URL-friendly slug, auto-generated from the title.');

            $table->text('description')
                ->comment('A description of the video content.');

            $table->date('scheduled_date')
                ->nullable()
                ->comment('The date this video is scheduled for publication.');

            // ------------------------------------------------------------------
            // Status
            // ------------------------------------------------------------------
            $table->string('status')
                ->default('not-published-to-youtube')
                ->comment('Publication status — see VideoStatus enum.');

            // ------------------------------------------------------------------
            // YouTube
            // ------------------------------------------------------------------
            $table->string('youtube_title')
                ->nullable()
                ->comment('The title used when publishing to YouTube.');

            $table->text('youtube_description')
                ->nullable()
                ->comment('The description used when publishing to YouTube.');

            $table->text('youtube_chapters')
                ->nullable()
                ->comment('Chapter markers for the YouTube video.');

            $table->string('youtube_url')
                ->nullable()
                ->comment('The public YouTube URL once published.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};