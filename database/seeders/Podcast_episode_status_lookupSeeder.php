<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Podcast_episode_status_lookupSeeder extends Seeder
{
    /**
     * Seed the podcast_episode_status_lookup table.
     *
     * IDs are explicit so that references to specific statuses (e.g. ID 7
     * for "published") remain stable. Re-sequenced from the original data
     * to fill the gap at position 10.
     */
    public function run(): void
    {
        DB::table('podcast_episode_status_lookup')->insert([
            [
                'id'          => 1,
                'title'       => 'create-an-episode',
                'description' => 'Create an episode',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 2,
                'title'       => 'composing-draft',
                'description' => 'Composing draft',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'title'       => 'ready-to-validate',
                'description' => 'Ready to validate for RSS feed',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'title'       => 'failed-validation',
                'description' => 'Failed validation',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 5,
                'title'       => 'passed-validation',
                'description' => 'Passed validation',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 6,
                'title'       => 'failed-to-publish',
                'description' => 'Failed to publish',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 7,
                'title'       => 'published',
                'description' => 'Published',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 8,
                'title'       => 'rss-feed-file-and-website-preparation-completed',
                'description' => 'Completed RSS Feed File & Website Preparation',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 9,
                'title'       => 'ready-to-create-production-audio-files-with-auphonicdotcom',
                'description' => 'Ready to create production audio files with auphonicdotcom',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 10,
                'title'       => 'ready-to-generate-the-rss-feed-file',
                'description' => 'Ready to generate the rss feed file',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 11,
                'title'       => 'ready-to-publish-on-the-website',
                'description' => 'Ready to publish this episode on the website',
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}