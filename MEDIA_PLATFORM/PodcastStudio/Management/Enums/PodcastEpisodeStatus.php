<?php

namespace MediaPlatform\PodcastStudio\Management\Enums;

enum PodcastEpisodeStatus: string
{
    // Episode has just been created via the create-episode wizard.
    case created                         = 'created';

    // Episode draft is being written.
    case working_on_draft                = 'working-on-draft';

    // Draft is complete — episode is ready for the raw WAV recording to be uploaded to S3.
    case ready_to_upload_recording       = 'ready-to-upload-recording';

    // Raw recording is uploaded — episode is ready to be submitted to Auphonic for processing.
    case ready_for_auphonic              = 'ready-for-auphonic';

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
            self::working_on_draft                => 'Working on Draft',
            self::ready_to_upload_recording       => 'Ready to Upload Recording to S3',
            self::ready_for_auphonic              => 'Ready for Auphonic',
            self::ready_to_upload_production_file => 'Ready to Upload Production File to S3 & R2',
            self::ready_to_generate_rss_feed      => 'Ready to Generate RSS Feed',
            self::ready_to_upload_rss_feed        => 'Ready to Upload RSS Feed to S3 & R2',
            self::ready_to_publish                => 'Ready to Publish on Website',
            self::published                       => 'Published',
        };
    }
}