<?php

// =============================================================================
// Migration: populate_user_id_on_podcast_links
//
// One-time data fix: assigns all existing podcast_links rows to user 1.
// Safe to run on a single-user app — all existing links belong to the
// sole user. Runs automatically on deploy after the nullable column
// migration (2026_05_19_000002) has added the column.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('podcast_links')
            ->whereNull('user_id')
            ->update(['user_id' => 1]);
    }

    public function down(): void
    {
        // Intentionally a no-op.
        // Rolling back would require knowing the original user_id per row,
        // which is not recoverable. The nullable column migration handles
        // the schema rollback.
    }
};