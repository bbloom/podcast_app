<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend the lists.output_type column to allow 'wordpress'.
 *
 * Laravel's ->enum() on PostgreSQL creates a VARCHAR column with a CHECK
 * constraint (not a native PG enum type). Confirmed by inspecting pg_constraint:
 *   lists_output_type_check: CHECK output_type IN ('webpage', 'email')
 *
 * The correct approach is to DROP the old constraint and ADD a new one
 * that includes 'wordpress'. This is non-destructive — existing rows are
 * unaffected, and the column type does not change.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint that only allows 'webpage' and 'email'.
        DB::statement("ALTER TABLE lists DROP CONSTRAINT lists_output_type_check");

        // Add the new constraint that also allows 'wordpress'.
        DB::statement("ALTER TABLE lists ADD CONSTRAINT lists_output_type_check CHECK (output_type IN ('webpage', 'email', 'wordpress'))");
    }

    public function down(): void
    {
        // Restore the original constraint (remove 'wordpress' support).
        // Any rows with output_type = 'wordpress' must be removed first or this will fail.
        DB::statement("ALTER TABLE lists DROP CONSTRAINT lists_output_type_check");

        DB::statement("ALTER TABLE lists ADD CONSTRAINT lists_output_type_check CHECK (output_type IN ('webpage', 'email'))");
    }
};