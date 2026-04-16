<?php

// =============================================================================
// Migration: create_deploy_hooks_table
//
// Stores deploy hook URLs for static site hosting providers (Cloudflare Pages,
// Netlify, Vercel, etc.). A deploy hook is a URL that, when POSTed to, triggers
// a fresh build of the associated static site front-end.
//
// Each hook belongs to a podcast show. A show may have multiple hooks —
// for example, a live site and a staging site on different providers.
//
// The hook URL is stored encrypted — it acts as a secret, and anyone who
// holds the URL can trigger a build.
//
// Path: database/migrations/media_platform/podcast_studio/management/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Enums\DeployHookProvider;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploy_hooks', function (Blueprint $table) {

            $table->comment('Deploy hooks for static site hosting providers. POSTing to the URL triggers a fresh site build. One show may have multiple hooks (e.g. live + staging).');

            $table->id();

            // ------------------------------------------------------------------
            // Ownership
            // ------------------------------------------------------------------

            $table->foreignId('podcast_show_id')
                  ->constrained('podcast_shows')
                  ->cascadeOnDelete()
                  ->comment('The podcast show this deploy hook belongs to.');

            // ------------------------------------------------------------------
            // Identity
            // ------------------------------------------------------------------

            $table->string('label')
                  ->comment('Human-readable name for this hook, e.g. "Bob Bloom Show — Cloudflare Pages (Live)".');

            // ------------------------------------------------------------------
            // Provider
            // ------------------------------------------------------------------

            $table->string('provider')
                  ->default(DeployHookProvider::cloudflare_pages->value)
                  ->comment('The hosting provider. Backed by the DeployHookProvider enum: cloudflare_pages, netlify, vercel.');

            // ------------------------------------------------------------------
            // Hook URL
            //
            // Stored encrypted — the URL is a secret. Anyone who holds it can
            // trigger a build. Uses Laravel's encrypted cast on the model.
            // ------------------------------------------------------------------

            $table->text('url')
                  ->comment('The deploy hook URL. Stored encrypted. POSTing to this URL triggers a build on the hosting provider.');

            // ------------------------------------------------------------------
            // Control
            // ------------------------------------------------------------------

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('Whether this hook is active. Disabled hooks are never triggered, manually or automatically.');

            $table->timestamp('last_triggered_at')
                  ->nullable()
                  ->comment('The last time this hook was successfully triggered. Null if never triggered.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_hooks');
    }
};