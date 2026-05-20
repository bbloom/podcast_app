<?php

namespace MediaPlatform\Podcasts\Planning\PrepareForPublishingWizard\Concerns;

use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use MediaPlatform\Podcasts\Planning\CRUD\Models\PodcastEpisodePlanning;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use Illuminate\Support\Str;

// =============================================================================
// DerivesPublishedEpisodeFields
//
// Adapted from MEDIA_PLATFORM/Podcasts/Step3Controller.php.txt.
// All population methods are rewritten to read from a PodcastEpisodePlanning
// record rather than a Laravel Request object.
//
// Used by both Step2Controller (for derived-value previews) and
// Step3Controller (for the actual published episode creation).
//
// Population methods are public to allow direct unit testing per conventions.
// =============================================================================

trait DerivesPublishedEpisodeFields
{
    // =========================================================================
    // GENERAL
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_title()                                                           │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Derive the published episode title.
     * Prepends "#N - " and truncates to 163 characters (Spotify iPhone limit).
     */
    public function get_title(PodcastEpisodePlanning $episode): string
    {
        $title  = $episode->title;
        $number = $this->get_itunes_episode($episode);
        $prefix = '#' . $number . ' - ';

        if (! str_starts_with($title, $prefix)) {
            $title = $prefix . $title;
        }

        return substr($title, 0, 163);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_slug()                                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Derive the episode slug from the show slug, episode number, and title.
     */
    public function get_slug(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        $title         = $this->get_title($episode);
        $episodeNumber = $this->get_itunes_episode($episode);
        $prefix        = '#' . $episodeNumber . ' - ';
        $titleForSlug  = substr($title, strlen($prefix));

        // Normalise the title for use in a slug.
        $titleForSlug = trim($titleForSlug);
        $titleForSlug = rtrim($titleForSlug, '!');
        $titleForSlug = str_replace("\xc2\xa0", '', $titleForSlug);  // encoded blank chars
        $titleForSlug = str_replace('&#39;',    '', $titleForSlug);  // encoded apostrophe
        $titleForSlug = str_replace(',',        '', $titleForSlug);  // comma
        $titleForSlug = str_replace("'",        '', $titleForSlug);  // apostrophe
        $titleForSlug = str_replace('"',        '', $titleForSlug);  // double quote
        $titleForSlug = html_entity_decode($titleForSlug);
        $titleForSlug = strip_tags($titleForSlug);
        $titleForSlug = strtolower($titleForSlug);
        $titleForSlug = str_replace(' ', '-', $titleForSlug);

        $base = $show->slug . '-ep' . $episodeNumber . '-';

        return substr($base . $titleForSlug, 0, 100);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_scheduled_date()                                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Return the scheduled date, defaulting to today if not set.
     */
    public function get_scheduled_date(PodcastEpisodePlanning $episode): string
    {
        return $episode->scheduled_date
            ? $episode->scheduled_date->toDateString()
            : now()->toDateString();
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_raw_input_audio_filename()                                        │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Derive the WAV filename from the normalised show title and episode number.
     */
    public function get_raw_input_audio_filename(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        return $this->normalise_show_title($show) . $this->get_itunes_episode($episode) . '.wav';
    }

    // =========================================================================
    // STATUS
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_status()                                                          │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * A freshly published episode always starts at the beginning of the pipeline.
     */
    public function get_status(): PodcastEpisodeStatus
    {
        return PodcastEpisodeStatus::created;
    }

    // =========================================================================
    // ITUNES
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_title_tag()                                                │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_title_tag(PodcastEpisodePlanning $episode): string
    {
        return $this->get_title($episode);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_enclosure_url()                                            │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_enclosure_url(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        $base = rtrim($show->storage_audio_files_url, '/') . '/';

        return $base . $this->normalise_show_title($show) . $this->get_itunes_episode($episode) . '.mp3';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_enclosure_type()                                           │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_enclosure_type(): string
    {
        return 'audio/mpeg';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_guid()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Generate a unique GUID in the format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.
     */
    public function get_itunes_guid(): string
    {
        return Str::random(8) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(12);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_pubdate()                                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_pubdate(PodcastEpisodePlanning $episode): string
    {
        return $this->get_scheduled_date($episode) . ' 00:00:00';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_description()                                              │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_description(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        return $this->get_website_content($episode) . $this->get_links_plain_text($episode, $show);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_link()                                                     │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_link(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        $base = rtrim($show->itunes_link, '/') . '/';

        return $base . 'episode/' . $this->get_slug($episode, $show);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_itunestitle_tag()                                          │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_itunestitle_tag(PodcastEpisodePlanning $episode): string
    {
        $title = $this->get_title($episode);

        if (str_starts_with($title, '#')) {
            return preg_replace('/#\d+ - /', '', $title);
        }

        return $title;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_episode()                                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_episode(PodcastEpisodePlanning $episode): int
    {
        return (int) ($episode->episode_number ?? 0);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_season()                                                   │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_season(): int
    {
        return 0;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_episode_type()                                             │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_episode_type(): string
    {
        return 'full';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_summary()                                                  │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_summary(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        return $this->get_itunes_description($episode, $show);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_subtitle()                                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_subtitle(PodcastEpisodePlanning $episode): string
    {
        return $this->get_itunes_itunestitle_tag($episode);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_itunes_content_encoded()                                          │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_itunes_content_encoded(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        return $this->get_website_content($episode) . $this->get_links_html($episode, $show);
    }

    // =========================================================================
    // WEBSITE
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_content()                                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Process the website content from the planning record.
     * Converts Markdown to HTML first (planning records may contain either),
     * then strips disallowed tags.
     */
    public function get_website_content(PodcastEpisodePlanning $episode): string
    {
        $text = $episode->website_content ?? '';

        // Convert Markdown to HTML (safe no-op for content already in HTML).
        $text = Str::markdown($text);

        // Normalise entities then strip to permitted tags.
        $text = html_entity_decode($text);
        $text = strip_tags($text, '<p><ol><ul><li><a>');

        return trim($text);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_excerpt()                                                 │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Use the planning record's excerpt if set; otherwise auto-derive from content.
     */
    public function get_website_excerpt(PodcastEpisodePlanning $episode): string
    {
        if ($episode->website_excerpt) {
            return trim(mb_substr(strip_tags($episode->website_excerpt), 0, 255));
        }

        $text = $this->get_website_content($episode);
        $text = html_entity_decode($text);
        $text = strip_tags($text);
        $text = str_replace("\xc2\xa0", '', $text);

        return trim(mb_substr($text, 0, 255));
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_meta_description()                                        │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_meta_description(PodcastEpisodePlanning $episode): string
    {
        return $this->get_website_excerpt($episode);
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution()                                             │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution(PodcastShow $show): string
    {
        return match ($show->title) {
            'The Bob Bloom Show'             => $this->get_website_attribution_for_the_bob_bloom_show(),
            'The Bob Bloom Interviews'       => $this->get_website_attribution_for_the_bob_bloom_interviews(),
            'PHP Serverless News'            => $this->get_website_attribution_for_php_serverless_news(),
            'PHP Serverless Profiles'        => $this->get_website_attribution_for_php_serverless_profiles(),
            'PHP Serverless Project Updates' => $this->get_website_attribution_for_php_serverless_project_updates(),
            default => throw new \RuntimeException(
                "DerivesPublishedEpisodeFields::get_website_attribution() — no attribution defined for show: \"{$show->title}\" (ID: {$show->id})"
            ),
        };
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_publish_on()                                              │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_publish_on(PodcastEpisodePlanning $episode): string
    {
        return $this->get_scheduled_date($episode);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  normalise_show_title()                                                │
    // └────────────────────────────────────────────────────────────────────────┘
    /**
     * Lowercase, remove spaces, strip leading "the".
     * Used in audio filename and enclosure URL derivation.
     */
    public function normalise_show_title(PodcastShow $show): string
    {
        $title = strtolower(str_replace(' ', '', $show->title));

        if (str_starts_with($title, 'the')) {
            $title = substr($title, 3);
        }

        return $title;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_links_plain_text()                                                │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_links_plain_text(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        $itunesLink = $this->get_itunes_link($episode, $show);

        return <<<END


Links:
• Episode's web page: {$itunesLink}
• Project site: https://phpserverlessproject.com
• Commentary podcast: https://bobbloomshow.com
• News podcast: https://phpserverlessnews.com
• Profiles podcast: https://phpserverlessprofiles.com
• Interviews podcast: https://bobbloominterviews.com
• YouTube channel: https://youtube.com/@phpserverlessproject
END;
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_links_html()                                                      │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_links_html(PodcastEpisodePlanning $episode, PodcastShow $show): string
    {
        $itunesLink = $this->get_itunes_link($episode, $show);

        return '<br><br>Links<ul>'
            . '<li>Episode\'s web page: <a href="' . $itunesLink . '">' . $itunesLink . '</a></li>'
            . '<li>Project site: <a href="https://phpserverlessproject.com">https://phpserverlessproject.com</a></li>'
            . '<li>Commentary podcast: <a href="https://bobbloomshow.com">https://bobbloomshow.com</a></li>'
            . '<li>News podcast: <a href="https://phpserverlessnews.com">https://phpserverlessnews.com</a></li>'
            . '<li>Profiles podcast: <a href="https://phpserverlessprofiles.com">https://phpserverlessprofiles.com</a></li>'
            . '<li>Interviews podcast: <a href="https://bobbloominterviews.com">https://bobbloominterviews.com</a></li>'
            . '<li>YouTube channel: <a href="https://youtube.com/@phpserverlessproject">https://youtube.com/@phpserverlessproject</a></li>'
            . '</ul>';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution_for_the_bob_bloom_show()                     │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution_for_the_bob_bloom_show(): string
    {
        return '<div>Beethoven\'s Symphony No. 1, Op.21:</div><ul><li><a href="https://creativecommons.org/licenses/by/4.0/legalcode">Creative Commons Attribution 4.0</a>&nbsp;</li><li><a href="https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.1,_Op.21_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution_for_the_bob_bloom_interviews()               │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution_for_the_bob_bloom_interviews(): string
    {
        return '';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution_for_php_serverless_news()                    │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution_for_php_serverless_news(): string
    {
        return '<div>Beethoven\'s Symphony No.8, Op.93:</div><ul><li><a href="https://imslp.org/wiki/IMSLP:Public_Domain">Public Domain</a></li><li><a href="https://imslp.org/wiki/Symphony_No.8,_Op.93_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.8,_Op.93_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution_for_php_serverless_profiles()                │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution_for_php_serverless_profiles(): string
    {
        return '<div><br>Beethoven\'s Symphony No.5, Op.67:</div><ul><li><a href="https://imslp.org/wiki/IMSLP:Creative_Commons_Attribution-ShareAlike_3.0">https://imslp.org/wiki/IMSLP:Creative_Commons_Attribution-ShareAlike_3.0</a></li><li><a href="https://imslp.org/wiki/Symphony_No.5%2C_Op.67_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.5%2C_Op.67_(Beethoven%2C_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>';
    }

    // ┌────────────────────────────────────────────────────────────────────────┐
    // │  get_website_attribution_for_php_serverless_project_updates()         │
    // └────────────────────────────────────────────────────────────────────────┘
    public function get_website_attribution_for_php_serverless_project_updates(): string
    {
        return '<div>Beethoven\'s Symphony No.8, Op.93:</div><ul><li><a href="https://imslp.org/wiki/IMSLP:Public_Domain">Public Domain</a></li><li><a href="https://imslp.org/wiki/Symphony_No.8,_Op.93_(Beethoven,_Ludwig_van)">https://imslp.org/wiki/Symphony_No.8,_Op.93_(Beethoven,_Ludwig_van)</a></li></ul><div><br>Beethoven\'s Symphony No.9, Op.125:</div><ul><li><a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode">Creative Commons Attribution-NoDerivs 4.0&nbsp;</a></li><li><a href="https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)">https://imslp.org/wiki/Symphony_No.9%2C_Op.125_(Beethoven%2C_Ludwig_van)</a></li></ul>';
    }
}