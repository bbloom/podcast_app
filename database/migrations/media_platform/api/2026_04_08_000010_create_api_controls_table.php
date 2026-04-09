<?php

// =============================================================================
// Migration: create_api_controls_table
//
// A single-row table that acts as a master on/off switch for the public API.
// The API is disabled by default. It is enabled manually via the Admin UI
// before an Astro build runs, and disabled again automatically by the
// scheduler after a configurable window, or manually afterwards.
//
// Path: database/migrations/media_platform/api/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_controls', function (Blueprint $table) {

            $table->comment('Master on/off switch for the public API. Contains a single row. The API is disabled by default and enabled only during Astro build windows.');

            $table->id();

            $table->boolean('is_enabled')
                  ->default(false)
                  ->comment('Whether the public API is currently accepting requests. False by default.');

            $table->timestamp('enabled_at')
                  ->nullable()
                  ->comment('The timestamp when the API was most recently enabled.');

            $table->timestamp('disabled_at')
                  ->nullable()
                  ->comment('The timestamp when the API was most recently disabled.');

            $table->string('notes')
                  ->nullable()
                  ->comment('Optional internal notes about the current API control state.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_controls');
    }
};