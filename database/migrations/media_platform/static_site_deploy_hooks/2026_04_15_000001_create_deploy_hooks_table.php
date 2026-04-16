<?php

// =============================================================================
// Migration: create_deploy_hooks_table
//
// Stores deploy hook URLs for static site hosting providers (Cloudflare Pages,
// Netlify, Vercel, etc.). A deploy hook is a URL that, when POSTed to, triggers
// a fresh build of the associated static site front-end.
//
// The hook is polymorphic — it can belong to any triggerable model, such as
// a PodcastShow or a Digest List. This allows the same deploy hook mechanism
// to be shared across multiple features of the platform.
//
// The hook URL is stored encrypted — it acts as a secret, and anyone who
// holds the URL can trigger a build.
//
// Path: database/migrations/media_platform/static_site_deploy_hooks/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploy_hooks', function (Blueprint $table) {

            $table->comment(
                'Deploy hooks for static site hosting providers. POSTing to the URL triggers a fresh site build. ' .
                'Polymorphic — can belong to a PodcastShow, a Digest List, or any other triggerable model. ' .
                'One triggerable model may have multiple hooks (e.g. live + staging).'
            );

            $table->id();

            // ------------------------------------------------------------------
            // Polymorphic ownership
            //
            // triggerable_type — the morph alias of the owning model
            //                    e.g. "podcast_show", "digest_list"
            // triggerable_id   — the primary key of the owning model
            // ------------------------------------------------------------------

            $table->string('triggerable_type')
                  ->comment('The morph alias of the owning model, e.g. "podcast_show" or "digest_list".');

            $table->unsignedBigInteger('triggerable_id')
                  ->comment('The primary key of the owning model.');

            $table->index(['triggerable_type', 'triggerable_id'], 'deploy_hooks_triggerable_index');

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
                  ->comment('Whether this hook is active. Disabled hooks are never triggered.');

            // ------------------------------------------------------------------
            // Trigger tracking
            //
            // Records the outcome of the most recent trigger attempt.
            // last_build_id is provider-specific — Cloudflare returns a build
            // UUID; other providers may return nothing (stored as null).
            // ------------------------------------------------------------------

            $table->timestamp('last_triggered_at')
                  ->nullable()
                  ->comment('The last time this hook was triggered. Null if never triggered.');

            $table->string('last_build_id')
                  ->nullable()
                  ->comment('The build identifier returned by the provider after triggering. Cloudflare returns a UUID. Null if the provider returns none.');

            $table->string('last_trigger_status')
                  ->nullable()
                  ->comment('The outcome of the last trigger attempt: "success" or "failed". Null if never triggered.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_hooks');
    }
};