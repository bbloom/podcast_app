<?php

namespace Database\Seeders;

use App\Models\User;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Database\Seeder;

/**
 * ListModelSeeder — seeds digest lists for development and testing.
 *
 * Creates one list per output type so all three delivery paths can be
 * explored in the UI. The static site list is required by
 * DeployHooksSeeder and PublishedDigestsSeeder.
 *
 * Gated behind ADMIN_SEEDING_ENABLED — the gate lives in DatabaseSeeder,
 * not here (per conventions).
 */
class ListModelSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', config('admin.admin_email'))->first();

        if (! $user) {
            $this->command->warn('ListModelSeeder: Admin user not found. Skipping.');
            return;
        }

        // ── Email list ────────────────────────────────────────────────────────
        ListModel::create([
            'user_id'               => $user->id,
            'name'                  => 'Daily AI Roundup',
            'description'           => 'AI and machine learning news delivered by email each morning.',
            'enabled'               => true,
            'schedule_frequency'    => 'daily',
            'schedule_day'          => null,
            'schedule_time'         => '07:00',
            'timezone'              => 'America/Toronto',
            'output_type'           => 'email',
            'output_destination_id' => null,
            'notify_by_email'       => false,
            'retention_count'       => 10,
        ]);

        // ── Webpage (SFTP) list ───────────────────────────────────────────────
        // Note: no output_destination_id — assign one manually via the UI
        // after creating an SFTP destination, or the seeder can reference
        // an existing OutputDestination if one is seeded before this runs.
        ListModel::create([
            'user_id'               => $user->id,
            'name'                  => 'Weekly PHP News',
            'description'           => 'PHP ecosystem updates published as a webpage via SFTP every Monday.',
            'enabled'               => true,
            'schedule_frequency'    => 'weekly',
            'schedule_day'          => 1,
            'schedule_time'         => '08:00',
            'timezone'              => 'America/Toronto',
            'output_type'           => 'webpage',
            'output_destination_id' => null,
            'notify_by_email'       => true,
            'retention_count'       => 10,
        ]);

        // ── Static Site list ──────────────────────────────────────────────────
        ListModel::create([
            'user_id'               => $user->id,
            'name'                  => 'Morning Tech Digest',
            'description'           => 'Daily tech updates from YouTube, podcasts, and RSS feeds — delivered to a static site.',
            'enabled'               => true,
            'schedule_frequency'    => 'daily',
            'schedule_day'          => null,
            'schedule_time'         => '06:00',
            'timezone'              => 'America/Toronto',
            'output_type'           => 'static_site',
            'output_destination_id' => null,
            'notify_by_email'       => true,
            'retention_count'       => 10,
        ]);

        $this->command->info('ListModelSeeder: Seeded 3 lists (email, webpage, static site).');
    }
}