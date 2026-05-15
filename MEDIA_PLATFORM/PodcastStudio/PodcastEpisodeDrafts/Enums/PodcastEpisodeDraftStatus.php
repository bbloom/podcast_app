<?php

// =============================================================================
// PodcastEpisodeDraftStatus
//
// Tracks where a draft is in its lifecycle.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Enums/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Enums;

enum PodcastEpisodeDraftStatus: string
{
    // Draft is being worked on — notes, body, planning.
    case working_on_draft                  = 'working-on-draft';

    // Pre-production wizard has been completed. All required fields are populated.
    // This draft is eligible for conversion to a production episode.
    case ready_to_create_production_episode = 'ready-to-create-production-episode';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::working_on_draft                   => 'Working on Draft',
            self::ready_to_create_production_episode => 'Ready to Create Production Episode',
        };
    }
}