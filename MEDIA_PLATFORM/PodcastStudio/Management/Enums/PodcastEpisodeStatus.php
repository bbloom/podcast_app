<?php

namespace MediaPlatform\PodcastStudio\Management\Enums;

enum PodcastEpisodeStatus: string
{
    // Episode has just been created via the create-episode wizard.
    case created                         = 'created';

    // Draft is complete — episode is ready for the raw WAV recording to be uploaded to S3.
    case ready_to_upload_recording       = 'ready-to-upload-recording';

    // Raw recording is uploaded — episode is ready to be submitted to Auphonic for processing.
    case ready_for_auphonic              = 'ready-for-auphonic';

    // Episode has been submitted to Auphonic and is currently being processed.
    // The Auphonic production UUID is stored on the episode record.
    // This status is set immediately after the API call succeeds.
    // Auphonic will call the webhook when processing is complete.
    case processing_at_auphonic          = 'processing-at-auphonic';

    // Auphonic has finished processing and called the webhook.
    // The episode is waiting for the user to review the MP3 in the Auphonic
    // console, or proceed directly to the next step.
    case auphonic_complete               = 'auphonic-complete';

    // Auphonic processing is complete — production audio file is ready to be uploaded to S3 and R2.
    case ready_to_upload_production_file = 'ready-to-upload-production-file';

    // Production file is uploaded — episode is ready for RSS feed file generation.
    case ready_to_generate_rss_feed      = 'ready-to-generate-rss-feed';

    // RSS feed file is generated — ready to be uploaded to S3 and R2.
    case ready_to_upload_rss_feed        = 'ready-to-upload-rss-feed';

    // RSS feed file is uploaded — episode is ready to be published on the website.
    case ready_to_publish                = 'ready-to-publish';

    // Episode is live on the website and in the RSS feed.
    case published                       = 'published';


    // -------------------------------------------------------------------------
    // Human-readable label for display in the UI.
    // -------------------------------------------------------------------------

    /**
     * Returns a human-readable label for display in dropdowns and status badges.
     */
    public function label(): string
    {
        return match($this) {
            self::created                         => 'Created',
            self::ready_to_upload_recording       => 'Ready to Upload Recording to S3',
            self::ready_for_auphonic              => 'Ready for Auphonic',
            self::processing_at_auphonic          => 'Processing at Auphonic',
            self::auphonic_complete               => 'Auphonic Complete',
            self::ready_to_upload_production_file => 'Ready to Upload Production File to S3 & R2',
            self::ready_to_generate_rss_feed      => 'Ready to Generate RSS Feed',
            self::ready_to_upload_rss_feed        => 'Ready to Upload RSS Feed to S3 & R2',
            self::ready_to_publish                => 'Ready to Publish on Website',
            self::published                       => 'Published',
        };
    }
}