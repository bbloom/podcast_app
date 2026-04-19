<?php

// =============================================================================
// Migration: create_published_digests_table
//
// Stores persisted digest payloads for the static site output type. One record
// per digest run per list. The API serves these to Astro (or any static site
// generator) during its build process.
//
// The payload column contains the full structured digest data as JSON, including
// source groups and individual items with their summaries. This makes the record
// self-contained — it does not depend on the summaries table, which is ephemeral.
//
// Path: database/migrations/media_platform/digests/processing/
// Already registered in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('published_digests', function (Blueprint $table) {

            $table->comment(
                'Persisted digest payloads for static site output type. One record per digest run per list. ' .
                'The API serves these to Astro during static site builds. Self-contained — does not depend on the ephemeral summaries table.'
            );

            $table->id();

            $table->foreignId('list_id')
                  ->constrained('lists')
                  ->cascadeOnDelete()
                  ->comment('The list this digest was built for.');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Owner of the list. Stored for query convenience and auditing.');

            $table->string('slug')
                  ->comment('URL-friendly identifier for this digest run, e.g. morning-tech-digest-2026-04-15. Used as the page path on the static site.');

            $table->date('digest_date')
                  ->comment('The date this digest pertains to.');

            $table->unsignedInteger('total_items')
                  ->comment('Number of content items in this digest.');

            $table->unsignedInteger('source_count')
                  ->comment('Number of distinct content sources in this digest.');

            $table->json('payload')
                  ->comment('Full structured digest data: groups with items including source_url, source_title, source_description, source_published_at, summary_html, source_type. Stored as JSON for API delivery.');

            $table->timestamp('deploy_hook_fired_at')
                  ->nullable()
                  ->comment('When the deploy hook was fired after persisting this digest. Null if not yet fired or if firing failed.');

            $table->timestamp('api_fetched_at')
                  ->nullable()
                  ->comment('When the static site generator last fetched this digest via the API. Null until fetched. Used for observability.');

            $table->timestamps();

            $table->index(['list_id', 'digest_date'], 'published_digests_list_date');
            $table->unique(['list_id', 'slug'], 'published_digests_list_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('published_digests');
    }
};