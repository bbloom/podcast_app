<?php

// =============================================================================
// Migration: create_api_clients_table
//
// Stores authorised API clients (e.g. Cloudflare/EmDash, DigitalOcean Astro).
// Each client has a domain name and a hashed bearer token. The token is
// generated in the Admin UI, shown once, then stored as a bcrypt hash.
//
// The RequestingDomain header is matched against the `domain` column.
// The Authorization: Bearer token is verified against the `token_hash` column.
// Both must match for a request to be authorised.
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
        Schema::create('api_clients', function (Blueprint $table) {

            $table->comment('Authorised API clients. Each client authenticates via a bearer token and a RequestingDomain header. Tokens are stored as bcrypt hashes and are never retrievable after generation.');

            $table->id();

            $table->string('label')
                  ->comment('Human-readable name for this client, e.g. "Cloudflare EmDash" or "DigitalOcean Astro".');

            $table->string('domain')
                  ->unique()
                  ->comment('The domain this client sends in the RequestingDomain header, e.g. "mypodcast.com".');

            $table->string('token_hash')
                  ->comment('Bcrypt hash of the bearer token. The plain-text token is shown once at generation and never stored.');

            $table->boolean('is_active')
                  ->default(true)
                  ->comment('Whether this client is currently permitted to access the API.');

            $table->timestamp('last_used_at')
                  ->nullable()
                  ->comment('The timestamp of the most recent successful request from this client.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};