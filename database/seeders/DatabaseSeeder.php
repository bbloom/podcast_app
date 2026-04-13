<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! config('admin.seeding_enabled')) {
            $this->command->warn('Seeding is disabled. Set ADMIN_SEEDING_ENABLED=true in .env to enable.');
            return;
        }
        
        $this->call([
            UsersSeeder::class,
            LlmSeeder::class,
            Podcast_showsSeeder::class,
            Podcast_episodes1Seeder::class,
            Podcast_episodes2Seeder::class,
            Podcast_episodes3Seeder::class,
            Podcast_episodes4Seeder::class,
            Podcast_linksSeeder::class,
            Podcast_link_episodeSeeder::class,
            PhpServerlessProjectSponsorsSeeder::class,
            ApiClientsSeeder::class,
        ]);
    }
}
