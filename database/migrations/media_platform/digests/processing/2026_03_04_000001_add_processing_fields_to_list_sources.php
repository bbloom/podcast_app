<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('list_sources', function (Blueprint $table) {
            $table->string('processing_mode')
                ->default('description')
                ->comment('How to process new content: description (metadata only), summary (transcript + Gemini), search (match terms then summarise)');

            $table->text('search_terms')
                ->nullable()
                ->comment('Comma-separated search terms for search mode. Matched against title, description, then transcript via Gemini semantic check');
        });
    }

    public function down(): void
    {
        Schema::table('list_sources', function (Blueprint $table) {
            $table->dropColumn(['processing_mode', 'search_terms']);
        });
    }
};
