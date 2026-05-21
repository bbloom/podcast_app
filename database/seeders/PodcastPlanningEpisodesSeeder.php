<?php

// =============================================================================
// PodcastPlanningEpisodesSeeder
//
// Seeds podcast_episodes_planning with 30 factory-produced planning episodes —
// 6 per active show, covering all 7 planning statuses, with future scheduled
// dates spread across days, weeks, and months.
//
// Also seeds 6 PodcastGuests and 6 PodcastLinks (via factories), and attaches
// them to a selection of episodes so every UI attachment surface has data.
//
// Prerequisites (already seeded by the standard seeder chain):
//   - The admin user (admin.admin_email)
//   - The 5 active podcast shows (Podcast_showsSeeder)
//
// Gated behind ADMIN_SEEDING_ENABLED — the gate lives in DatabaseSeeder.
// =============================================================================


// =============================================================================
// * Shows are looked up, not created — the seeder expects the 5 shows to already exist 
//   from Podcast_showsSeeder with user_id matching the admin user. If any show isn't 
//   found it warns and skips rather than crashing.
// 
// * Status distribution — the 7 statuses cycle across 30 episodes (6 per show). 
//   Every status appears at least 4 times across the dataset, giving you good 
//   coverage on every planning view.
// 
// * Fields are populated realistically — theme and script are only non-null on statuses 
//   where you'd expect them to exist. website_content and website_excerpt are only populated 
//   on ready_for_publishing. 
//   Trying to view the Recording View on a ready_to_record episode will show a real script.
// 
// * Guest/link attachments — only on statuses where it makes sense (you wouldn't 
//   have links attached to a new_episode_created episode).
// =============================================================================



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Podcasts\Links\Models\PodcastLink;
use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PodcastPlanningEpisodesSeeder extends Seeder
{
    // The five active shows, in display order.
    // Must match the titles already inserted by Podcast_showsSeeder.
    private const ACTIVE_SHOWS = [
        'The Bob Bloom Show',
        'The Bob Bloom Interviews',
        'PHP Serverless News',
        'PHP Serverless Profiles',
        'PHP Serverless Project Updates',
    ];

    // 6 scheduled dates per show — spread across days, weeks, and months ahead.
    private const SCHEDULED_DATES = [
        '+5 days',
        '+2 weeks',
        '+3 weeks',
        '+5 weeks',
        '+2 months',
        '+4 months',
    ];

    // 7 planning statuses cycled across the 6 episodes per show.
    // Statuses 0–5 map to episodes 0–5; the 7th status wraps to episode 0
    // on the next show so all statuses appear across the full dataset.
    private const STATUSES = [
        PodcastEpisodePlanningStatus::new_episode_created,
        PodcastEpisodePlanningStatus::working_on_theme,
        PodcastEpisodePlanningStatus::writing_script,
        PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
        PodcastEpisodePlanningStatus::ready_to_record,
        PodcastEpisodePlanningStatus::raw_audio_needs_editing,
        PodcastEpisodePlanningStatus::ready_for_publishing,
    ];

    public function run(): void
    {
        $user = User::where('email', config('admin.admin_email'))->first();

        if (! $user) {
            $this->command->warn('PodcastPlanningEpisodesSeeder: Admin user not found. Skipping.');
            return;
        }

        // ── Reset PostgreSQL sequences ────────────────────────────────────────
        // The Podcast_linksSeeder and podcast_guests table insert with explicit
        // IDs, leaving the sequences at 1. Reset them before factory calls so
        // auto-increment picks up after the highest existing ID.
        DB::statement("SELECT setval(pg_get_serial_sequence('podcast_guests', 'id'), COALESCE((SELECT MAX(id) FROM podcast_guests), 0) + 1, false)");
        DB::statement("SELECT setval(pg_get_serial_sequence('podcast_links', 'id'), COALESCE((SELECT MAX(id) FROM podcast_links), 0) + 1, false)");

        // ── Guests ────────────────────────────────────────────────────────────
        $guests = PodcastGuest::factory()->count(6)->create();

        // ── Links ─────────────────────────────────────────────────────────────
        $links = PodcastLink::factory()->count(6)->create(['user_id' => $user->id]);

        // ── Planning episodes — 6 per show ────────────────────────────────────
        $statusIndex  = 0;
        $episodeIndex = 1; // running episode number across all shows

        foreach (self::ACTIVE_SHOWS as $showTitle) {
            $show = PodcastShow::where('title', $showTitle)
                ->where('user_id', $user->id)
                ->first();

            if (! $show) {
                $this->command->warn("PodcastPlanningEpisodesSeeder: Show not found — \"{$showTitle}\". Skipping.");
                continue;
            }

            foreach (self::SCHEDULED_DATES as $offset) {
                $status = self::STATUSES[$statusIndex % 7];

                $episode = PodcastEpisodePlanning::factory()->create([
                    'user_id'         => $user->id,
                    'podcast_show_id' => $show->id,
                    'status'          => $status,
                    'episode_number'  => $episodeIndex,
                    'scheduled_date'  => now()->modify($offset)->toDateString(),
                    'theme'           => $this->themeFor($status),
                    'script'          => $this->scriptFor($status),
                    'website_content' => $this->websiteContentFor($status),
                    'website_excerpt' => $this->websiteExcerptFor($status),
                ]);

                // Attach guests and links to episodes that are far enough
                // along in the pipeline to realistically have them.
                if ($this->shouldHaveGuests($status)) {
                    $episode->guests()->attach(
                        $guests->random(rand(1, 2))->pluck('id')
                    );
                }

                if ($this->shouldHaveLinks($status)) {
                    $episode->links()->attach(
                        $links->random(rand(1, 3))->pluck('id')
                    );
                }

                $statusIndex++;
                $episodeIndex++;
            }
        }

        $this->command->info('PodcastPlanningEpisodesSeeder: 30 planning episodes seeded.');
    }

    // =========================================================================
    // Helpers — populate fields appropriate to each status
    // =========================================================================

    private function themeFor(PodcastEpisodePlanningStatus $status): ?string
    {
        $needsTheme = [
            PodcastEpisodePlanningStatus::working_on_theme,
            PodcastEpisodePlanningStatus::writing_script,
            PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
            PodcastEpisodePlanningStatus::ready_to_record,
            PodcastEpisodePlanningStatus::raw_audio_needs_editing,
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ];

        return in_array($status, $needsTheme, true)
            ? fake()->paragraph()
            : null;
    }

    private function scriptFor(PodcastEpisodePlanningStatus $status): ?string
    {
        $needsScript = [
            PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
            PodcastEpisodePlanningStatus::ready_to_record,
            PodcastEpisodePlanningStatus::raw_audio_needs_editing,
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ];

        return in_array($status, $needsScript, true)
            ? fake()->paragraphs(4, true)
            : null;
    }

    private function websiteContentFor(PodcastEpisodePlanningStatus $status): ?string
    {
        $needsWebsiteContent = [
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ];

        return in_array($status, $needsWebsiteContent, true)
            ? fake()->paragraphs(3, true)
            : null;
    }

    private function websiteExcerptFor(PodcastEpisodePlanningStatus $status): ?string
    {
        $needsExcerpt = [
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ];

        return in_array($status, $needsExcerpt, true)
            ? fake()->sentence()
            : null;
    }

    private function shouldHaveGuests(PodcastEpisodePlanningStatus $status): bool
    {
        return in_array($status, [
            PodcastEpisodePlanningStatus::writing_script,
            PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
            PodcastEpisodePlanningStatus::ready_to_record,
            PodcastEpisodePlanningStatus::raw_audio_needs_editing,
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ], true);
    }

    private function shouldHaveLinks(PodcastEpisodePlanningStatus $status): bool
    {
        return in_array($status, [
            PodcastEpisodePlanningStatus::ready_to_finalize_the_script,
            PodcastEpisodePlanningStatus::ready_to_record,
            PodcastEpisodePlanningStatus::raw_audio_needs_editing,
            PodcastEpisodePlanningStatus::ready_for_publishing,
        ], true);
    }
}