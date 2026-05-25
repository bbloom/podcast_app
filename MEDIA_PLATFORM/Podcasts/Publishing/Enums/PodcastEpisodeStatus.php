<?php

namespace MediaPlatform\Podcasts\Publishing\Enums;

// =============================================================================
// PodcastEpisodeStatus
//
// Tracks the post-production pipeline lifecycle for a published episode.
// Lives in the podcast_episodes_published table (string column — no migration
// needed when adding new cases).
//
// Pipeline order (new):
//   ready_to_upload_recording
//   → ready_for_auphonic → processing_at_auphonic → auphonic_complete
//   → ready_to_upload_production_file
//   → ready_to_publish_website       [NEW — RSS Pipeline Reorder]
//   → website_published              [NEW — RSS Pipeline Reorder]
//   → build_triggered                [Feature #1 — Cloudflare automation]
//   → ready_to_generate_rss_feed
//   → ready_to_upload_rss_feed       (repurposed: S3 uploaded, awaiting R2)
//   → rss_validation_failed          [NEW — RSS Pipeline Reorder]
//   → published
// =============================================================================

enum PodcastEpisodeStatus: string
{
    // Pipeline entry point — set by PrepareForPublishingWizard Step 3.
    case ready_to_upload_recording       = 'ready-to-upload-recording';

    case ready_for_auphonic              = 'ready-for-auphonic';

    // Auphonic is processing — webhook advances this to auphonic_complete.
    case processing_at_auphonic          = 'processing-at-auphonic';

    case auphonic_complete               = 'auphonic-complete';

    case ready_to_upload_production_file = 'ready-to-upload-production-file';

    // NEW — Set by UploadProductionAudio after MP3 is uploaded to S3 + R2.
    // Entry point for PublishOnWebsite in the reordered pipeline.
    case ready_to_publish_website        = 'ready-to-publish-website';

    // NEW — Set by PublishOnWebsite after website_enabled = true.
    // Entry point for TriggerBuilds in the reordered pipeline.
    case website_published               = 'website-published';

    // Set by TriggerBuildsController (pipeline context) after firing deploy hooks.
    // Entry point for BuildConfirmation.
    case build_triggered                 = 'build-triggered';

    // Set by BuildConfirmation after the static site build is confirmed complete.
    // Entry point for GenerateRssFeed.
    // Also used as the reset target by RestartController (from rss_validation_failed).
    case ready_to_generate_rss_feed      = 'ready-to-generate-rss-feed';

    // Repurposed — previously mapped to GenerateRssFeed Step 4 (staging validation).
    // Now means: RSS XML is on the live S3 bucket, awaiting R2 upload after
    // manual validation. Set by GenerateRssFeed Step 5 after S3 upload.
    case ready_to_upload_rss_feed        = 'ready-to-upload-rss-feed';

    // NEW — Set by LiveValidationController when the user marks validation as failed.
    // Dashboard shows this as "RSS Validation Failed — Needs Attention".
    // Entry point for GenerateRssFeed restart (via RestartController).
    case rss_validation_failed           = 'rss-validation-failed';

    // Legacy — set by the old pipeline's GenerateRssFeed Step 5.
    // Still used as the entry for PublishOnWebsite in any pre-reorder episodes.
    // Retained for backwards compatibility.
    case ready_to_publish                = 'ready-to-publish';

    // Episode is fully live — website, S3, and R2.
    case published                       = 'published';

    // Set manually. Episode was recorded but intentionally not published.
    case not_published                   = 'not-published';

    // =========================================================================
    // Human-readable label
    // =========================================================================

    public function label(): string
    {
        return match ($this) {
            self::ready_to_upload_recording       => 'Ready to Upload Recording to S3',
            self::ready_for_auphonic              => 'Ready for Auphonic',
            self::processing_at_auphonic          => 'Processing at Auphonic',
            self::auphonic_complete               => 'Auphonic Complete',
            self::ready_to_upload_production_file => 'Ready to Upload Production File to S3 & R2',
            self::ready_to_publish_website        => 'Ready to Publish on Website',
            self::website_published               => 'Published on Website — Awaiting Build',
            self::build_triggered                 => 'Build Triggered — Awaiting Completion',
            self::ready_to_generate_rss_feed      => 'Ready to Generate RSS Feed',
            self::ready_to_upload_rss_feed        => 'RSS on S3 — Awaiting Live Validation',
            self::rss_validation_failed           => 'RSS Validation Failed — Needs Attention',
            self::ready_to_publish                => 'Ready to Publish on Website (legacy)',
            self::published                       => 'Published',
            self::not_published                   => 'Not Published',
        };
    }

    // =========================================================================
    // Post-production route mapping
    //
    // The route always requires a {podcastEpisode} parameter.
    // Used by the dashboard Continue button and status guards.
    // =========================================================================

    public function postProductionShowRoute(): string
    {
        return match ($this) {
            self::ready_to_upload_recording       => 'post_production.upload_recording.show',
            self::ready_for_auphonic              => 'post_production.auphonic_processing.show',
            self::processing_at_auphonic          => 'post_production.auphonic_processing.show',
            self::auphonic_complete               => 'post_production.auphonic_processing.complete',
            self::ready_to_upload_production_file => 'post_production.upload_production_audio.show',
            self::ready_to_publish_website        => 'post_production.publish_on_website.show',
            self::website_published               => 'post_production.prepare_trigger_builds',
            self::build_triggered                 => 'post_production.build_confirmation.show',
            self::ready_to_generate_rss_feed      => 'post_production.generate_rss_feed.step1',
            self::ready_to_upload_rss_feed        => 'post_production.generate_rss_feed.live_validation',
            self::rss_validation_failed           => 'post_production.generate_rss_feed.restart',
            self::ready_to_publish                => 'post_production.publish_on_website.show',
            self::published, self::not_published  => 'podcast_episodes.index',
        };
    }
}