<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        $this->call([
            Podcast_episodes1ASeeder::class,
        ]);


    }


    /**
     * Seed the application's database.
     */
    public function donotrun(): void
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
        ]);
        }
    }
}
