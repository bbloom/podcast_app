<?php

// =============================================================================
// Migration: create_phpserverlessproject_sponsors_table
//
// Standalone lookup table for PHPServerlessProject sponsors. No foreign keys
// or relationships to any other table in this application.
//
// Path: database/migrations/media_platform/tools/phpserverlessproject_sponsors/
// Register this path in AppServiceProvider::boot() via loadMigrationsFrom().
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phpserverlessproject_sponsors', function (Blueprint $table) {

            $table->comment('Sponsors of the PHPServerlessProject. Standalone table — no relationships to other tables.');

            $table->id();

            $table->string('full_name')
                  ->unique()
                  ->comment('First and last name of the sponsor.');

            $table->string('image_url')
                  ->nullable()
                  ->comment('URL to the sponsor\'s full-size profile image.');

            $table->string('image_thumbnail_url')
                  ->nullable()
                  ->comment('URL to the sponsor\'s thumbnail image.');

            $table->text('profile_full')
                  ->comment('Full biography or profile text for the sponsor.');

            $table->string('profile_short')
                  ->nullable()
                  ->comment('Short one-line profile or tagline for the sponsor.');

            $table->string('link_to_sponsor_website')
                  ->nullable()
                  ->comment('URL to the sponsor\'s website.');

            $table->string('email_address')
                  ->comment('Sponsor\'s email address. Not related to the application\'s users table.');

            $table->boolean('umbrella_sponsor')
                  ->default(true)
                  ->comment('Whether this sponsor is an umbrella-level sponsor.');

            $table->boolean('basecamp_sponsor')
                  ->default(false)
                  ->comment('Whether this sponsor is a Basecamp-level sponsor.');

            $table->boolean('restream_sponsor')
                  ->default(false)
                  ->comment('Whether this sponsor is a Restream-level sponsor.');

            $table->boolean('former_sponsor')
                  ->default(false)
                  ->comment('Whether this person is a former (no longer active) sponsor.');

            $table->text('internal_comment')
                  ->nullable()
                  ->comment('Internal notes about this sponsor. Not for publishing.');

            $table->boolean('enabled')
                  ->default(true)
                  ->comment('Whether this sponsor record is active and visible.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phpserverlessproject_sponsors');
    }
};