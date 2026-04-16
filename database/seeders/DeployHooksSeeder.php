<?php

// =============================================================================
// DeployHooksSeeder
//
// Seeds the deploy_hooks table with one Cloudflare Pages deploy hook per
// active podcast show.
//
// This seeder is for LOCAL DEVELOPMENT AND TESTING ONLY.
// It is gated behind ADMIN_SEEDING_ENABLED in DatabaseSeeder — it will
// never run in production unless that flag is explicitly set to true,
// which it never should be in a production environment.
//
// The deploy hook URLs are fake — they follow the real Cloudflare Pages
// deploy hook URL format but use placeholder UUIDs. They will return
// errors if actually triggered, which is expected in a seeded environment.
//
// Polymorphic relationship:
//   triggerable_type = 'podcast_show'
//   triggerable_id   = the podcast show's id
//
// Show IDs match those in Podcast_showsSeeder:
//   3  — The Bob Bloom Show
//   4  — The Bob Bloom Interviews
//   10 — PHP Serverless News
//   11 — PHP Serverless Profiles
//   12 — PHP Serverless Project Updates
// =============================================================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;

class DeployHooksSeeder extends Seeder
{
    /**
     * Seed deploy hooks for each active podcast show.
     *
     * Each show gets one live Cloudflare Pages hook and one staging hook,
     * so the selection UI has multiple options to interact with.
     *
     * URLs are encrypted automatically by the model's encrypted cast —
     * but since we are using DB::table() directly here, we must encrypt
     * them manually using Laravel's encrypt() helper.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // The url column uses Laravel's 'encrypted' cast on the model.
        // DB::table()->insert() bypasses the model, so we encrypt manually.
        // ---------------------------------------------------------------------

        $now = now();

        DB::table('deploy_hooks')->insert([

            // -----------------------------------------------------------------
            // The Bob Bloom Show (show_id = 3)
            // -----------------------------------------------------------------
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 3,
                'label'               => 'The Bob Bloom Show — Cloudflare Pages — Live',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/aaa00001-0000-0000-0000-000000000001'),
                'enabled'             => true,
                'last_triggered_at'   => null,
                'last_build_id'       => null,
                'last_trigger_status' => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 3,
                'label'               => 'The Bob Bloom Show — Cloudflare Pages — Staging',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/aaa00001-0000-0000-0000-000000000002'),
                'enabled'             => true,
                'last_triggered_at'   => $now->copy()->subDays(2),
                'last_build_id'       => 'aaa00001-fake-build-id-staging',
                'last_trigger_status' => 'success',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // -----------------------------------------------------------------
            // The Bob Bloom Interviews (show_id = 4)
            // -----------------------------------------------------------------
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 4,
                'label'               => 'The Bob Bloom Interviews — Cloudflare Pages — Live',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/bbb00004-0000-0000-0000-000000000001'),
                'enabled'             => true,
                'last_triggered_at'   => $now->copy()->subHours(3),
                'last_build_id'       => 'bbb00004-fake-build-id-live',
                'last_trigger_status' => 'success',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 4,
                'label'               => 'The Bob Bloom Interviews — Netlify — Staging',
                'provider'            => DeployHookProvider::netlify->value,
                'url'                 => encrypt('https://api.netlify.com/build_hooks/bbb00004fake0000staging'),
                'enabled'             => false,
                'last_triggered_at'   => $now->copy()->subDays(10),
                'last_build_id'       => null,
                'last_trigger_status' => 'failed',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // -----------------------------------------------------------------
            // PHP Serverless News (show_id = 10)
            // -----------------------------------------------------------------
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 10,
                'label'               => 'PHP Serverless News — Cloudflare Pages — Live',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/ccc00010-0000-0000-0000-000000000001'),
                'enabled'             => true,
                'last_triggered_at'   => $now->copy()->subDay(),
                'last_build_id'       => 'ccc00010-fake-build-id-live',
                'last_trigger_status' => 'success',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // -----------------------------------------------------------------
            // PHP Serverless Profiles (show_id = 11)
            // -----------------------------------------------------------------
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 11,
                'label'               => 'PHP Serverless Profiles — Cloudflare Pages — Live',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/ddd00011-0000-0000-0000-000000000001'),
                'enabled'             => true,
                'last_triggered_at'   => null,
                'last_build_id'       => null,
                'last_trigger_status' => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 11,
                'label'               => 'PHP Serverless Profiles — Vercel — Staging',
                'provider'            => DeployHookProvider::vercel->value,
                'url'                 => encrypt('https://api.vercel.com/v1/integrations/deploy/ddd00011fake0000staging'),
                'enabled'             => true,
                'last_triggered_at'   => $now->copy()->subWeek(),
                'last_build_id'       => null,
                'last_trigger_status' => 'success',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // -----------------------------------------------------------------
            // PHP Serverless Project Updates (show_id = 12)
            // -----------------------------------------------------------------
            [
                'triggerable_type'    => 'podcast_show',
                'triggerable_id'      => 12,
                'label'               => 'PHP Serverless Project Updates — Cloudflare Pages — Live',
                'provider'            => DeployHookProvider::cloudflare_pages->value,
                'url'                 => encrypt('https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/eee00012-0000-0000-0000-000000000001'),
                'enabled'             => true,
                'last_triggered_at'   => $now->copy()->subHours(1),
                'last_build_id'       => 'eee00012-fake-build-id-live',
                'last_trigger_status' => 'success',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

        ]);
    }
}