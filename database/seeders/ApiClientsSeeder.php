<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MediaPlatform\API\v1\Models\ApiClient;

class ApiClientsSeeder extends Seeder
{
    /**
     * Seed the api_clients table with the five podcast front-end domains.
     *
     * Gated by DatabaseSeeder — will not run unless ADMIN_SEEDING_ENABLED=true.
     *
     * Tokens are seeded as a placeholder hash. Use the Admin UI to rotate
     * each client's token when you are ready to connect a front-end.
     *
     * Note: LaSalle Software News (lasallesoftwarenews.com) is intentionally
     * excluded — it does not have a front-end that consumes the API.
     */
    public function run(): void
    {
        $clients = [
            [
                'label'  => 'The Bob Bloom Show',
                'domain' => 'bobbloomshow.com',
            ],
            [
                'label'  => 'The Bob Bloom Interviews',
                'domain' => 'bobbloominterviews.com',
            ],
            [
                'label'  => 'PHP Serverless News',
                'domain' => 'phpserverlessnews.com',
            ],
            [
                'label'  => 'PHP Serverless Profiles',
                'domain' => 'phpserverlessprofiles.com',
            ],
            [
                'label'  => 'PHP Serverless Project Updates',
                'domain' => 'phpserverlessprojectupdates.com',
            ],
        ];

        foreach ($clients as $data) {

            // Skip if this domain already exists — safe to re-run.
            if (ApiClient::where('domain', $data['domain'])->exists()) {
                $this->command->warn("Skipped (already exists): {$data['domain']}");
                continue;
            }

            ApiClient::create([
                'label'      => $data['label'],
                'domain'     => $data['domain'],
                'token_hash' => Hash::make('placeholder-rotate-this-token-before-use'),
                'is_active'  => false,
            ]);

           // $this->command->info("Created: {$data['label']} ({$data['domain']})");
        }
    }
}