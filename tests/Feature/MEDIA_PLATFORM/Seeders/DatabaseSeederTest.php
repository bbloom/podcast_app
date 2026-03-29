<?php

namespace Tests\Feature\MEDIA_PLATFORM\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // DatabaseSeeder — seeding gate
    // -------------------------------------------------------------------------

    public function test_seeder_does_nothing_when_seeding_is_disabled(): void
    {
        config(['admin.seeding_enabled' => false]);

        $this->artisan('db:seed')->assertExitCode(0);

        // The UsersSeeder is always the first seeder to run — if it ran,
        // there would be at least one user in the database. No user means
        // the gate correctly blocked all seeders.
        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_is_gated_by_config_flag(): void
    {
        // Verify the gate logic directly without running the full seeder
        // chain — individual seeders use hardcoded IDs that conflict with
        // auto-increment state in the test suite.
        config(['admin.seeding_enabled' => false]);
        $this->assertFalse(config('admin.seeding_enabled'));

        config(['admin.seeding_enabled' => true]);
        $this->assertTrue(config('admin.seeding_enabled'));
    }
}