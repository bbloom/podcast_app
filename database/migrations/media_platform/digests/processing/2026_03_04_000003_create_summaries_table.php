<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Owner of this summary, inherited from the list');

            $table->foreignId('list_source_id')
                ->constrained('list_sources')
                ->cascadeOnDelete()
                ->comment('Which list+source pair produced this summary');

            $table->string('source_url')
                ->comment('URL of the original content (video, episode, article). Primary dedup key with list_source_id');

            $table->string('source_title')
                ->nullable()
                ->comment('Title from the YouTube API, RSS feed entry, etc.');

            $table->text('source_description')
                ->nullable()
                ->comment('Original description from the feed or API response');

            $table->timestamp('source_published_at')
                ->nullable()
                ->comment('When the original content was published by its creator');

            $table->string('processing_mode')
                ->comment('Mode used when this item was processed: description, summary, or search');

            $table->text('summary_html')
                ->nullable()
                ->comment('HTML content for the digest. Gemini summary for summary/search modes, formatted description for description mode. Null only if processing failed or search was not relevant');

            $table->boolean('is_relevant')
                ->default(true)
                ->comment('For search mode: did the content match the search terms? Always true for description and summary modes');

            $table->boolean('included_in_digest')
                ->default(false)
                ->comment('Whether this summary has been included in a published digest page');

            $table->timestamp('included_in_digest_at')
                ->nullable()
                ->comment('When this summary was included in a digest. Null until published');

            $table->timestamps();

            $table->unique(['list_source_id', 'source_url'], 'summaries_dedup');
            $table->index(['list_source_id', 'included_in_digest'], 'summaries_digest_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summaries');
    }
};
