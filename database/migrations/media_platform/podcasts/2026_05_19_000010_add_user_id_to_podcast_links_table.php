<?php

// =============================================================================
// Migration: add_user_id_to_podcast_links_table
//
// Adds a nullable user_id foreign key to podcast_links.
// Nullable so existing rows are unaffected — populate them via tinker:
//   DB::table('podcast_links')->whereNull('user_id')->update(['user_id' => 1]);
//
// Path: database/migrations/media_platform/podcasts/
// Registered in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('podcast_links', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('The user who owns this link.');
        });
    }

    public function down(): void
    {
        Schema::table('podcast_links', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};