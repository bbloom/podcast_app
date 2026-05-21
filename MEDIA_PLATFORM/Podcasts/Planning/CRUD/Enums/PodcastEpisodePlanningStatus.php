<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Enums;

// =============================================================================
// PodcastEpisodePlanningStatus
//
// Tracks the lifecycle of a podcast episode during the Planning phase.
// Lives in the podcast_episodes_planning table.
//
// Key design decisions:
//   - Statuses can move BACKWARDS — no forward-only enforcement.
//   - Data is NEVER cleared when a status moves backwards.
//   - Some statuses are set automatically by wizards; others are set manually
//     by the user via the episode's show page.
//   - This enum is deliberately separate from PodcastEpisodeStatus, which
//     tracks the Post-Production pipeline on podcast_episodes_published.
// =============================================================================

enum PodcastEpisodePlanningStatus: string
{
    // -------------------------------------------------------------------------
    // Set automatically by the Create Episode Wizard on completion.
    // -------------------------------------------------------------------------
    case new_episode_created          = 'new-episode-created';

    // -------------------------------------------------------------------------
    // Set manually by the user.
    // Signals: actively working on the theme / high-level topic notes.
    // -------------------------------------------------------------------------
    case working_on_theme             = 'working-on-theme';

    // -------------------------------------------------------------------------
    // Set manually by the user.
    // Signals: script writing is in progress.
    // -------------------------------------------------------------------------
    case writing_script               = 'writing-script';

    // -------------------------------------------------------------------------
    // Set manually by the user — a deliberate act, not an automatic transition.
    // Signals: writing is done; ready for final polish, intro/outro, proofread.
    // This status is the ENTRY POINT for the Finalize Script Wizard.
    // -------------------------------------------------------------------------
    case ready_to_finalize_the_script = 'ready-to-finalize-the-script';

    // -------------------------------------------------------------------------
    // Set automatically by the Finalize Script Wizard on completion.
    // Signals: script is locked with intro and outro; ready to record.
    // -------------------------------------------------------------------------
    case ready_to_record              = 'ready-to-record';

    // -------------------------------------------------------------------------
    // Set manually by the user — optional status after recording.
    // Signals: raw audio has been recorded and needs editing/trimming before
    // it is ready for Auphonic. The external audio editing stages are not
    // tracked by this application.
    // -------------------------------------------------------------------------
    case raw_audio_needs_editing      = 'raw-audio-needs-editing';

    // -------------------------------------------------------------------------
    // Set manually by the user — a conscious declaration.
    // Signals: WAV file is ready AND all website content is complete.
    // This status is the ENTRY POINT for the Prepare for Publishing Wizard,
    // which hands the episode off to podcast_episodes_published.
    // -------------------------------------------------------------------------
    case ready_for_publishing         = 'ready-for-publishing';

    // -------------------------------------------------------------------------
    // Tailwind CSS classes for the status badge in views.
    // -------------------------------------------------------------------------
    public function cssClass(): string
    {
        return match ($this) {
            self::new_episode_created          => 'text-base bg-gray-100 text-gray-700',
            self::working_on_theme             => 'bg-blue-100 text-blue-700',
            self::writing_script               => 'bg-amber-100 text-amber-700',
            self::ready_to_finalize_the_script => 'bg-orange-100 text-orange-700',
            self::ready_to_record              => 'bg-green-100 text-green-700',
            self::raw_audio_needs_editing      => 'bg-red-100 text-red-700',
            self::ready_for_publishing         => 'bg-purple-100 text-purple-700',
        };
    }

    // -------------------------------------------------------------------------
    // Human-readable label for display in views and UI.
    // -------------------------------------------------------------------------
    public function label(): string
    {
        return match ($this) {
            self::new_episode_created          => 'New Episode Created',
            self::working_on_theme             => 'Working On Theme',
            self::writing_script               => 'Writing Script',
            self::ready_to_finalize_the_script => 'Ready To Finalize The Script',
            self::ready_to_record              => 'Ready To Record',
            self::raw_audio_needs_editing      => 'Raw Audio Needs Editing',
            self::ready_for_publishing         => 'Ready For Publishing',
        };
    }

    // -------------------------------------------------------------------------
    // Returns all statuses that the user can set manually (i.e. not
    // automatically set by a wizard). Used to build status-change dropdowns
    // on the episode show/edit pages.
    // -------------------------------------------------------------------------
    public static function manualStatuses(): array
    {
        return [
            self::working_on_theme,
            self::writing_script,
            self::ready_to_finalize_the_script,
            self::raw_audio_needs_editing,
            self::ready_for_publishing,
        ];
    }

    // -------------------------------------------------------------------------
    // Returns the ordinal position of this status in the planning pipeline.
    // Used to sort episodes by workflow progression on the dashboard.
    // Lower number = earlier in the pipeline.
    // -------------------------------------------------------------------------

    public function sortOrder(): int
    {
        return match ($this) {
            self::new_episode_created          => 0,
            self::working_on_theme             => 1,
            self::writing_script               => 2,
            self::ready_to_finalize_the_script => 3,
            self::ready_to_record              => 4,
            self::raw_audio_needs_editing      => 5,
            self::ready_for_publishing         => 6,
        };
    }
}