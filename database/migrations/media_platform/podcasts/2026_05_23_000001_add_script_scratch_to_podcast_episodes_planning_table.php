<?php

// =============================================================================
// Migration: add_script_scratch_to_podcast_episodes_planning_table
//
// Adds script_scratch as a nullable text column on podcast_episodes_planning.
//
// Purpose: resilience storage for the Step 4 (AI Proofing) scratch pad in the
// Finalize Script Wizard. The user pastes AI-modified script content here for
// comparison against the canonical `script` field. Persisted to survive power
// outages, browser crashes, and session timeouts during the editing process.
//
// Permanent value: zero. Cleared by Step 9 (Confirm) when the wizard
// completes successfully.
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
        Schema::table('podcast_episodes_planning', function (Blueprint $table) {
            $table->text('script_scratch')
                  ->nullable()
                  ->after('script')
                  ->comment(
                      'Ephemeral scratch pad for the Finalize Script Wizard Step 4 (AI Proofing). ' .
                      'User pastes AI-modified script here for side-by-side comparison with the ' .
                      'canonical `script` field. Cleared on wizard completion (Step 9 store). ' .
                      'Populated = advisory shown on Podcasts Dashboard.'
                  );
        });
    }

    public function down(): void
    {
        Schema::table('podcast_episodes_planning', function (Blueprint $table) {
            $table->dropColumn('script_scratch');
        });
    }
};