<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_based_rss_feeds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('References users.id');

            $table->string('rss_url')
                  ->comment('The RSS feed URL');

            $table->string('title')
                  ->comment('Feed title as returned by the RSS feed');

            $table->text('description')
                  ->nullable()
                  ->comment('Feed level description as returned by the RSS feed');

            $table->string('site_url')
                  ->nullable()
                  ->comment('The website the feed belongs to');

            $table->string('thumbnail')
                  ->nullable()
                  ->comment('Feed image URL, UI falls back to default SVG if null');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled');

            $table->timestamps();

            $table->unique(['user_id', 'rss_url'], 'text_based_rss_feeds_user_url_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_based_rss_feeds');
    }
};
