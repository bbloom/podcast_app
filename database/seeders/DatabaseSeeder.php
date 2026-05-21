<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Prevents the seeding from being duplicated in production.
        // Because, need to run some seeders in production!
        if (! config('admin.seeding_enabled')) {
            $this->command->warn('Seeding is disabled. Set ADMIN_SEEDING_ENABLED=true in .env to enable.');
            return;
        }

        // run theses seeders
        $this->call([
            UsersSeeder::class,
            LlmSeeder::class,
            Podcast_showsSeeder::class,
            Podcast_episodes1Seeder::class,
            Podcast_episodes1ASeeder::class,
            Podcast_episodes2Seeder::class,
            Podcast_episodes3Seeder::class,
            Podcast_episodes4Seeder::class,
            Podcast_linksSeeder::class,
            Podcast_link_episodeSeeder::class,
            PhpServerlessProjectSponsorsSeeder::class,
            ApiClientsSeeder::class,
        ]);


        // Ok, so we are running the seeders. But, do not run these seeders in production at all
        if (! app()->environment('production')) {
            $this->call([
                DeployHooksSeeder::class,
                PublishedDigestsSeeder::class,
                ListModelSeeder::class,
                PodcastPlanningEpisodesSeeder::class,
        ]);
        }

        /* 
        Exactly. This is a classic PostgreSQL issue. Your seeders insert episodes with explicit id values (like 1 through 30), but PostgreSQL's auto-increment sequence doesn't advance when you insert with explicit IDs. So after seeding, the sequence is still at 1 (or wherever it was), and the next INSERT without an explicit ID generates an ID that already exists.

        This tells PostgreSQL "the next ID should come after the highest existing one." You may want to check your other seeded tables too — any table where your seeders insert explicit IDs will have the same problem. Likely candidates: podcast_shows, podcast_guests, podcast_links, and any other tables with hardcoded IDs in seeders.

        */

        DB::statement("SELECT setval(pg_get_serial_sequence('podcast_episodes_published', 'id'), (SELECT MAX(id) FROM podcast_episodes_published))");
    }
}