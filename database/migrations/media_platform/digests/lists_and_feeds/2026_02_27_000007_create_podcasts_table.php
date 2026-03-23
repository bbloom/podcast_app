<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcasts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('References users.id');

            $table->string('rss_url')
                  ->comment('The podcast RSS feed URL');

            $table->string('title')
                  ->comment('Podcast title as returned by the RSS feed');

            $table->text('description')
                  ->nullable()
                  ->comment('Podcast level description as returned by the RSS feed');

            $table->string('site_url')
                  ->nullable()
                  ->comment('The podcast\'s website');

            $table->string('thumbnail')
                  ->nullable()
                  ->comment('Podcast cover art URL from RSS feed, UI falls back to default SVG if null');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled');

            $table->timestamps();

            $table->unique(['user_id', 'rss_url'], 'podcasts_user_url_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcasts');
    }
};
