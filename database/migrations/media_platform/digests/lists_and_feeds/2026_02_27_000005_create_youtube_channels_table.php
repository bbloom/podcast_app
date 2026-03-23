<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_channels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('References users.id');

            $table->string('channel_id')
                  ->comment('Youtube channel ID e.g. UCxxxxxxxxxxxxxx, as returned by Youtube API');

            $table->string('title')
                  ->comment('Channel name as returned by Youtube API');

            $table->string('handle')
                  ->nullable()
                  ->comment('Channel handle e.g. @mkbhd, nullable as not all channels have handles');

            $table->string('channel_url')
                  ->comment('Channel URL e.g. https://www.youtube.com/@mkbhd');

            $table->string('rss_url')
                  ->comment('Youtube RSS feed URL, constructed from channel_id');

            $table->string('thumbnail')
                  ->nullable()
                  ->comment('Channel thumbnail URL from Youtube API, UI falls back to default SVG if null');

            $table->text('description')
                  ->nullable()
                  ->comment('Channel description as returned by Youtube API');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('User controlled');

            $table->timestamps();

            $table->unique(['user_id', 'channel_id'], 'youtube_channels_user_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_channels');
    }
};
