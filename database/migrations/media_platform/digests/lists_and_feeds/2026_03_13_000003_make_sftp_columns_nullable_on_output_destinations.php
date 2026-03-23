<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make SFTP-specific columns nullable on output_destinations.
 *
 * WHY THIS IS NEEDED
 * ──────────────────
 * When output_destinations was first created, all SFTP columns (host, port,
 * username, auth_type) were NOT NULL because only SFTP destinations existed.
 * Now that WordPress destinations are supported, these columns must be nullable
 * so that a WordPress row can be inserted without providing SFTP values.
 *
 * Also extends the output_destinations.type check constraint to include
 * 'wordpress', following the same VARCHAR-check-constraint pattern as the
 * lists.output_type column on this PostgreSQL setup.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Make SFTP-specific columns nullable ───────────────────────────────
        // These were previously NOT NULL because SFTP was the only type.
        // WordPress destinations do not use these columns.
        Schema::table('output_destinations', function (Blueprint $table) {
            $table->string('host')->nullable()->change();
            $table->unsignedSmallInteger('port')->nullable()->change();
            $table->string('username')->nullable()->change();
            $table->string('auth_type')->nullable()->change();
        });

        // ── Extend the type check constraint to include 'wordpress' ───────────
        // Drop the existing constraint and add a new one with the extra value.
        // Same approach as the lists.output_type migration.
        DB::statement('ALTER TABLE output_destinations DROP CONSTRAINT IF EXISTS output_destinations_type_check');
        DB::statement("ALTER TABLE output_destinations ADD CONSTRAINT output_destinations_type_check CHECK (type IN ('sftp', 'api', 'wordpress'))");
    }

    public function down(): void
    {
        // Restore the original constraint (removes 'wordpress' support).
        // Any rows with type = 'wordpress' must be removed first.
        DB::statement('ALTER TABLE output_destinations DROP CONSTRAINT IF EXISTS output_destinations_type_check');
        DB::statement("ALTER TABLE output_destinations ADD CONSTRAINT output_destinations_type_check CHECK (type IN ('sftp', 'api'))");

        // Re-adding NOT NULL constraints is safe only if no WordPress rows exist.
        Schema::table('output_destinations', function (Blueprint $table) {
            $table->string('host')->nullable(false)->change();
            $table->unsignedSmallInteger('port')->nullable(false)->change();
            $table->string('username')->nullable(false)->change();
            $table->string('auth_type')->nullable(false)->change();
        });
    }
};