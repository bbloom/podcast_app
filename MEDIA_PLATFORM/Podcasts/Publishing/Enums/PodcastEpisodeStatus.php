<?php

namespace MediaPlatform\Podcasts\Publishing\Enums;

// =============================================================================
// PodcastEpisodeStatus
//
// Tracks the post-production pipeline lifecycle for a published episode.
// Lives in the podcast_episodes_published table.
//
// Key design decisions:
//   - Statuses generally move FORWARD through the pipeline.
//   - The `created` status has been removed — episodes enter the pipeline at
//     `ready_to_upload_recording` (set by PrepareForPublishingWizard Step 3).
//   - `ready_to_upload_recording` is retained for now as the pipeline entry
//     point. Will be removed once the Post-Production entry point is refactored
//     to use `ready_for_publishing` from the planning world.
//   - This enum is deliberately separate from PodcastEpisodePlanningStatus,
//     which tracks the planning lifecycle on podcast_episodes_planning.
//
// Moved from: MEDIA_PLATFORM/Podcasts/Enums/PodcastEpisodeStatus.php
// New path:   MEDIA_PLATFORM/Podcasts/Publishing/Enums/PodcastEpisodeStatus.php
// =============================================================================

enum PodcastEpisodeStatus: string
{
    // Episode is ready for the raw WAV recording to be uploaded to S3.
    // This is the pipeline entry point — set by PrepareForPublishingWizard Step 3.
    case ready_to_upload_recording       = 'ready-to-upload-recording';

    // Raw recording is uploaded — episode is ready to be submitted to Auphonic.
    case ready_for_auphonic              = 'ready-for-auphonic';

    // Episode has been submitted to Auphonic and is currently being processed.
    // Auphonic will call the webhook when processing is complete.
    case processing_at_auphonic          = 'processing-at-auphonic';

    // Auphonic has finished processing and called the webhook.
    // The episode is waiting for review or clean-up.
    case auphonic_complete               = 'auphonic-complete';

    // Auphonic processing is complete — production audio file is ready to be
    // uploaded to S3 and R2.
    case ready_to_upload_production_file = 'ready-to-upload-production-file';

    // Production file is uploaded — episode is ready for RSS feed generation.
    // NOTE: In the RSS Pipeline Reorder (see RSS_PIPELINE_REORDER_PLAN.md),
    // this status will be replaced by `ready_to_publish_website`. Retained
    // here until the full reorder is implemented.
    case ready_to_generate_rss_feed      = 'ready-to-generate-rss-feed';

    // RSS feed file is generated and staged — ready for external validation
    // and promotion to live S3 + R2.
    case ready_to_upload_rss_feed        = 'ready-to-upload-rss-feed';

    // RSS feed file is live — episode is ready to be published on the website.
    case ready_to_publish                = 'ready-to-publish';

    // Static site deploy hooks have been triggered — the episode is waiting
    // for the Cloudflare Pages build to complete before RSS generation begins.
    // Set by TriggerBuildsController when operating in the pipeline context.
    // Part of the RSS Pipeline Reorder — see RSS_PIPELINE_REORDER_PLAN.md.
    case build_triggered                 = 'build-triggered';

    // Episode is live on the website and in the RSS feed.
    case published                       = 'published';

    // Episode was recorded but intentionally not published. Set manually.
    case not_published                   = 'not-published';

    // =========================================================================
    // Human-readable label
    // =========================================================================

    /**
     * Returns a human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::ready_to_upload_recording       => 'Ready to Upload Recording to S3',
            self::ready_for_auphonic              => 'Ready for Auphonic',
            self::processing_at_auphonic          => 'Processing at Auphonic',
            self::auphonic_complete               => 'Auphonic Complete',
            self::ready_to_upload_production_file => 'Ready to Upload Production File to S3 & R2',
            self::ready_to_generate_rss_feed      => 'Ready to Generate RSS Feed',
            self::ready_to_upload_rss_feed        => 'Ready to Upload RSS Feed to S3 & R2',
            self::ready_to_publish                => 'Ready to Publish on Website',
            self::build_triggered                 => 'Build Triggered — Awaiting Completion',
            self::published                       => 'Published',
            self::not_published                   => 'Not Published',
        };
    }

    // =========================================================================
    // Post-production route mapping
    // =========================================================================

    /**
     * Returns the named route for the episode-specific post-production page
     * for this status. The route always requires a {podcastEpisode} parameter.
     */
    public function postProductionShowRoute(): string
    {
        return match ($this) {
            self::ready_to_upload_recording       => 'post_production.upload_recording.show',
            self::ready_for_auphonic              => 'post_production.auphonic_processing.show',
            self::processing_at_auphonic          => 'post_production.auphonic_processing.show',
            self::auphonic_complete               => 'post_production.auphonic_processing.complete',
            self::ready_to_upload_production_file => 'post_production.upload_production_audio.show',
            self::ready_to_generate_rss_feed      => 'post_production.generate_rss_feed.step1',
            self::ready_to_upload_rss_feed        => 'post_production.generate_rss_feed.step4',
            self::ready_to_publish                => 'post_production.publish_on_website.show',
            self::build_triggered                 => 'post_production.build_confirmation.show',
            self::published, self::not_published  => 'podcast_episodes.index',
        };
    }
}