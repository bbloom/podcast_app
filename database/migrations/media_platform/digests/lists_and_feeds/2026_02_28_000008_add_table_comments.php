<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("COMMENT ON TABLE output_destinations IS 'Stores SFTP and API destination servers where list summaries are uploaded or sent. One destination can be shared across multiple lists.'");

        DB::statement("COMMENT ON TABLE lists IS 'Stores user-defined lists of sources. Each list has a schedule, an output destination, and contains one or more sources of any type. The central orchestration concept of the application.'");

        DB::statement("COMMENT ON TABLE list_sources IS 'Polymorphic pivot table connecting lists to their sources. A source can be a YoutubeChannel, TextBasedRssFeed, or Podcast. Tracks both user-controlled and system-controlled enabled/suspended state per source per list.'");

        DB::statement("COMMENT ON TABLE youtube_channels IS 'Stores Youtube channels added by users. Channel metadata is fetched from the Youtube Data API v3 at the time of adding. One row per user per channel.'");

        DB::statement("COMMENT ON TABLE text_based_rss_feeds IS 'Stores text-based RSS feed sources such as blogs, news sites, and press releases. Content is text only — no audio or video. One row per user per feed URL.'");

        DB::statement("COMMENT ON TABLE podcasts IS 'Stores podcast sources identified by their RSS feed URL. Podcast episodes contain audio files requiring transcription, distinguishing them from text-based RSS feeds. One row per user per feed URL.'");
    }

    public function down(): void
    {
        // The down() method sets comments to NULL rather than an empty string — that's the 
        // correct Postgres way to remove a comment entirely.
        DB::statement("COMMENT ON TABLE output_destinations IS NULL");
        DB::statement("COMMENT ON TABLE lists IS NULL");
        DB::statement("COMMENT ON TABLE list_sources IS NULL");
        DB::statement("COMMENT ON TABLE youtube_channels IS NULL");
        DB::statement("COMMENT ON TABLE text_based_rss_feeds IS NULL");
        DB::statement("COMMENT ON TABLE podcasts IS NULL");
    }
};