<?php

namespace MediaPlatform\PodcastStudio\PostProduction\GenerateRssFeed\Services;

// Models
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;

// Native PHP
use DOMDocument;

// =============================================================================
// RssFeedGeneratorService
//
// Generates the RSS XML feed for a given podcast show.
//
// This service is the sole owner of XML generation logic. It has no knowledge
// of wizard steps, episode status transitions, file uploads, or HTTP requests.
// It accepts a PodcastShow model and returns a GenerateRssFeedResult value
// object — callers check $result->ok() before using $result->xml().
//
// Ported and adapted from the ls-podcastrssfeedbackend-pkg package.
// Key fixes applied during port:
//   - boolToYesNo() returns lowercase 'yes'/'no' (Apple spec requires lowercase)
//   - itunes:image item-level tag now uses href attribute (not text content)
//   - Unused namespace declarations removed (cc, creativeCommons, dc, rdf, slash, media)
// =============================================================================

class RssFeedGeneratorService
{
    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Generate the complete RSS XML for the given podcast show.
     *
     * Returns a GenerateRssFeedResult value object. The caller must check
     * $result->ok() before using $result->xml(). No exceptions are thrown —
     * errors are communicated via $result->error().
     *
     * Artisan command:  if (! $result->ok()) { $this->error($result->error()); return; }
     * Wizard controller: if (! $result->ok()) { redirect back with $result->error(); }
     */
    public function generate(PodcastShow $show): GenerateRssFeedResult
    {
        // ---------------------------------------------------------------------
        // Fetch eligible episodes.
        // Only episodes where rss_feed_enabled = true and itunes_pubdate is in
        // the past are included. Ordered most-recent-first.
        // ---------------------------------------------------------------------
         $episodes = PodcastEpisode::eligibleForRssFeed($show)->get();

        if ($episodes->isEmpty()) {
            return GenerateRssFeedResult::failure(
                "No eligible episodes found for show \"{$show->title}\" (ID: {$show->id}). " .
                "Episodes must have rss_feed_enabled = true and a pubdate in the past."
            );
        }

        // ---------------------------------------------------------------------
        // Initialise the DOMDocument.
        // UTF-8 encoding, formatted output for human readability.
        // ---------------------------------------------------------------------
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;

        // ---------------------------------------------------------------------
        // Build the document tree.
        // ---------------------------------------------------------------------
        $root    = $this->buildRootTag($dom);
        $channel = $this->buildChannelTag($dom, $root, $show, $episodes);
        $this->buildItemTags($dom, $channel, $show, $episodes);

        return GenerateRssFeedResult::success($dom->saveXML());
    }

    /**
     * Derive the RSS filename for a given podcast show.
     *
     * Converts the show slug to underscores, prefixed with "rss_".
     * Example: "bob-bloom-show" → "rss_bob_bloom_show.xml"
     */
    public function getFileName(PodcastShow $show): string
    {
        $base = str_replace('-', '_', $show->slug);

        return 'rss_' . $base . '.xml';
    }


    // =========================================================================
    // ROOT TAG
    // =========================================================================

    /**
     * Build the <rss> root element with namespace declarations and version="2.0".
     *
     * Namespace declarations kept:
     *   atom            — required for atom:link self-referencing tag
     *   content         — required for content:encoded tags
     *   itunes          — required for all iTunes/Apple Podcasts tags
     *   googleplay      — accepted by Spotify and legacy directory scrapers
     *   podcast         — Podcast Index namespace (future: transcripts, chapters)
     *
     * Namespace declarations removed vs. original package:
     *   cc, creativeCommons  — legacy Creative Commons, unused by any current directory
     *   dc                   — Dublin Core, declared but never emitted
     *   media                — Yahoo Media RSS, declared but never emitted
     *   rdf                  — RDF syntax, only needed for RSS 1.0 (we use RSS 2.0)
     *   slash                — Slashdot comment counts, irrelevant to podcasting
     */
    private function buildRootTag(DOMDocument $dom): \DOMElement
    {
        $root  = $dom->createElement('rss');
        $xmlns = 'http://www.w3.org/2000/xmlns/';

        $root->setAttributeNS($xmlns, 'xmlns:atom',       'http://www.w3.org/2005/Atom');
        $root->setAttributeNS($xmlns, 'xmlns:content',    'http://purl.org/rss/1.0/modules/content/');
        $root->setAttributeNS($xmlns, 'xmlns:googleplay', 'http://www.google.com/schemas/play-podcasts/1.0');
        $root->setAttributeNS($xmlns, 'xmlns:itunes',     'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $root->setAttributeNS($xmlns, 'xmlns:podcast',    'http://podcastindex.org/namespace/1.0');

        $versionAttr        = $dom->createAttribute('version');
        $versionAttr->value = '2.0';
        $root->appendChild($versionAttr);

        $dom->appendChild($root);

        return $root;
    }


    // =========================================================================
    // CHANNEL TAG
    // =========================================================================

    /**
     * Build the <channel> element and all its child tags (show-level metadata).
     * Returns the channel element so item tags can be appended to it.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $episodes
     */
    private function buildChannelTag(
        DOMDocument $dom,
        \DOMElement $root,
        PodcastShow $show,
        iterable    $episodes
    ): \DOMElement {
        $channel = $root->appendChild($dom->createElement('channel'));

        // --- Non-iTunes structural tags --------------------------------------

        // atom:link — self-referencing feed URL, required by feed validators.
        $atomLink = $dom->createElement('atom:link');
        $atomLink->setAttribute('href', $show->rss_link);
        $atomLink->setAttribute('rel',  'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);

        // pubDate — publish date of the most recent episode.
        $channel->appendChild(
            $dom->createElement('pubDate', $episodes->first()->itunes_pubdate->format('D, d M Y H:i:s T'))
        );

        // lastBuildDate — timestamp of when this feed was generated.
        $channel->appendChild(
            $dom->createElement('lastBuildDate', now()->format('D, d M Y H:i:s T'))
        );

        // generator — identifies this software as the feed producer.
        $channel->appendChild(
            $dom->createElement('generator', 'LaSalle Software Podcast RSS Feed Generator')
        );

        // docs — points to the show's website (mirrors original package behaviour).
        $channel->appendChild(
            $dom->createElement('docs', $show->itunes_link)
        );

        // --- Required iTunes channel tags ------------------------------------

        // title
        $channel->appendChild($dom->createElement('title', $show->title));

        // description — CDATA wrapped, truncated to 4000 bytes.
        $descriptionTag = $dom->createElement('description');
        $descriptionTag->appendChild(
            $dom->createCDATASection(substr($show->description, 0, 4000))
        );
        $channel->appendChild($descriptionTag);

        // itunes:image — href attribute carries the artwork URL.
        $itunesImage = $dom->createElement('itunes:image');
        $itunesImage->setAttribute('href', $show->itunes_image);
        $channel->appendChild($itunesImage);

        // language — ISO 639 code, e.g. "en".
        $channel->appendChild($dom->createElement('language', $show->itunes_language));

        // itunes:category — primary, with optional nested secondary.
        $this->appendCategoryTags($dom, $channel, $show);

        // itunes:explicit — lowercase yes/no per Apple spec.
        $channel->appendChild(
            $dom->createElement('itunes:explicit', $this->boolToYesNo($show->itunes_explicit))
        );

        // --- Recommended iTunes channel tags ---------------------------------

        // itunes:author
        $channel->appendChild($dom->createElement('itunes:author', $show->itunes_author));

        // link
        $channel->appendChild($dom->createElement('link', $show->itunes_link));

        // itunes:owner — wraps email and name.
        $owner = $channel->appendChild($dom->createElement('itunes:owner'));
        $owner->appendChild($dom->createElement('itunes:email', $show->itunes_email));
        $owner->appendChild($dom->createElement('itunes:name',  $show->itunes_name));

        // --- Situational iTunes channel tags ---------------------------------

        // itunes:title
        $channel->appendChild($dom->createElement('itunes:title', $show->itunes_title));

        // itunes:type — "episodic" or "serial".
        $channel->appendChild($dom->createElement('itunes:type', $show->itunes_type));

        // copyright — CDATA wrapped.
        $copyrightTag = $dom->createElement('copyright');
        $copyrightTag->appendChild($dom->createCDATASection($show->itunes_copyright));
        $channel->appendChild($copyrightTag);

        // itunes:new-feed-url — only emitted when populated.
        if (! $this->blank($show->itunes_new_feed_url)) {
            $channel->appendChild(
                $dom->createElement('itunes:new-feed-url', $show->itunes_new_feed_url)
            );
        }

        // itunes:block — prevents show appearing in Apple Podcasts directory.
        $channel->appendChild(
            $dom->createElement('itunes:block', $this->boolToYesNo($show->itunes_block))
        );

        // itunes:complete — signals no future episodes will be published.
        $channel->appendChild(
            $dom->createElement('itunes:complete', $this->boolToYesNo($show->itunes_complete))
        );

        // --- Spotify tags ----------------------------------------------------

        // spotify:limit — only emitted when greater than zero.
        if ($show->spotify_limit > 0) {
            $spotifyLimit = $dom->createElement('spotify:limit');
            $spotifyLimit->setAttribute('recentCount', $show->spotify_limit);
            $channel->appendChild($spotifyLimit);
        }

        // spotify:countryOfOrigin — omitted when blank or "global".
        if (! $this->blank($show->spotify_country_of_origin) &&
            strtolower($show->spotify_country_of_origin) !== 'global') {
            $channel->appendChild(
                $dom->createElement('spotify:countryOfOrigin', $show->spotify_country_of_origin)
            );
        }

        // --- Other channel tags ----------------------------------------------

        // itunes:summary — fallback to description when blank.
        $summaryText = $this->blank($show->itunes_summary)
            ? substr($show->description, 0, 4000)
            : substr($show->itunes_summary, 0, 4000);
        $channel->appendChild($dom->createElement('itunes:summary', $summaryText));

        // itunes:subtitle — fallback to website_excerpt when blank.
        $subtitleText = $this->blank($show->itunes_subtitle)
            ? $show->website_excerpt
            : $show->itunes_subtitle;
        $channel->appendChild($dom->createElement('itunes:subtitle', $subtitleText));

        // <image> — standard RSS 2.0 image block (distinct from itunes:image above).
        $imageBlock = $channel->appendChild($dom->createElement('image'));
        $imageBlock->appendChild($dom->createElement('url',   $show->itunes_image));
        $imageBlock->appendChild($dom->createElement('title', $show->title));
        $imageBlock->appendChild($dom->createElement('link',  $show->itunes_link));

        // content:encoded — CDATA wrapped, fallback to description when blank.
        $contentEncoded = $this->blank($show->itunes_content_encoded)
            ? substr($show->description, 0, 4000)
            : substr($show->itunes_content_encoded, 0, 4000);
        $contentEncodedTag = $dom->createElement('content:encoded');
        $contentEncodedTag->appendChild($dom->createCDATASection($contentEncoded));
        $channel->appendChild($contentEncodedTag);

        return $channel;
    }

    /**
     * Append itunes:category tag(s) to the channel.
     * When a secondary category exists it is nested inside the primary tag,
     * per the Apple Podcasts RSS spec.
     */
    private function appendCategoryTags(
        DOMDocument $dom,
        \DOMElement $channel,
        PodcastShow $show
    ): void {
        if ($this->blank($show->itunes_category_secondary)) {

            // Primary category only.
            $primary = $dom->createElement('itunes:category');
            $primary->setAttribute('text', htmlspecialchars_decode($show->itunes_category_primary));
            $channel->appendChild($primary);

        } else {

            // Primary with nested secondary.
            $primary = $dom->createElement('itunes:category');
            $primary->setAttribute('text', htmlspecialchars_decode($show->itunes_category_primary));
            $channel->appendChild($primary);

            $secondary = $dom->createElement('itunes:category');
            $secondary->setAttribute('text', htmlspecialchars_decode($show->itunes_category_secondary));
            $primary->appendChild($secondary);
        }
    }


    // =========================================================================
    // ITEM TAGS
    // =========================================================================

    /**
     * Append one <item> element per episode to the channel element.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $episodes
     */
    private function buildItemTags(
        DOMDocument $dom,
        \DOMElement $channel,
        PodcastShow $show,
        iterable    $episodes
    ): void {
        foreach ($episodes as $episode) {

            $item = $channel->appendChild($dom->createElement('item'));

            // --- Required iTunes item tags -----------------------------------

            // title
            $item->appendChild($dom->createElement('title', $episode->title));

            // enclosure — the MP3 file reference. url, length (bytes), type.
            $enclosure = $dom->createElement('enclosure');
            $enclosure->setAttribute('url',    $episode->itunes_enclosure_url);
            $enclosure->setAttribute('length', $episode->itunes_enclosure_length);
            $enclosure->setAttribute('type',   $episode->itunes_enclosure_type);
            $item->appendChild($enclosure);

            // --- Recommended iTunes item tags --------------------------------

            // guid — isPermaLink="false" because this is a UUID, not a URL.
            $guid = $dom->createElement('guid', $episode->itunes_guid);
            $guid->setAttribute('isPermaLink', 'false');
            $item->appendChild($guid);

            // pubDate — RFC 2822 format as required by RSS 2.0.
            $item->appendChild(
                $dom->createElement('pubDate', $episode->itunes_pubdate->format('D, d M Y H:i:s T'))
            );

            // description — CDATA wrapped, truncated to 4000 bytes.
            $descriptionTag = $dom->createElement('description');
            $descriptionTag->appendChild(
                $dom->createCDATASection(substr($episode->itunes_description, 0, 4000))
            );
            $item->appendChild($descriptionTag);

            // itunes:duration — HH:MM:SS format or total seconds (both accepted).
            $item->appendChild(
                $dom->createElement('itunes:duration', $episode->itunes_duration)
            );

            // link — episode's webpage URL.
            $item->appendChild($dom->createElement('link', $episode->itunes_link));

            // itunes:image — episode-specific artwork. Uses href attribute.
            // Fixed from original package which incorrectly used text content.
            // Only emitted when the episode has its own artwork (not all do).
            if (! $this->blank($episode->itunes_image)) {
                $episodeImage = $dom->createElement('itunes:image');
                $episodeImage->setAttribute('href', $episode->itunes_image);
                $item->appendChild($episodeImage);
            }

            // itunes:explicit — lowercase yes/no.
            $item->appendChild(
                $dom->createElement('itunes:explicit', $this->boolToYesNo($episode->itunes_explicit))
            );

            // --- Situational iTunes item tags --------------------------------

            // itunes:title — only emitted when populated.
            if (! $this->blank($episode->itunes_itunestitle_tag)) {
                $item->appendChild(
                    $dom->createElement('itunes:title', $episode->itunes_itunestitle_tag)
                );
            }

            // itunes:episode — emitted when set, including 0.
            if (! is_null($episode->itunes_episode)) {
                $item->appendChild(
                    $dom->createElement('itunes:episode', $episode->itunes_episode)
                );
            }

            // itunes:season — emitted when set, including 0.
            if (! is_null($episode->itunes_season)) {
                $item->appendChild(
                    $dom->createElement('itunes:season', $episode->itunes_season)
                );
            }

            // itunes:episodeType — "full", "trailer", or "bonus".
            $item->appendChild(
                $dom->createElement('itunes:episodeType', $episode->itunes_episode_type)
            );

            // itunes:block — prevents this episode appearing in Apple Podcasts.
            $item->appendChild(
                $dom->createElement('itunes:block', $this->boolToYesNo($episode->itunes_block))
            );

            // --- Other item tags ---------------------------------------------

            // itunes:summary — fallback to itunes_description when blank.
            $summaryText = $this->blank($episode->itunes_summary)
                ? substr($episode->itunes_description, 0, 4000)
                : substr($episode->itunes_summary, 0, 4000);
            $item->appendChild($dom->createElement('itunes:summary', $summaryText));

            // itunes:subtitle — fallback to show-level subtitle when blank.
            $subtitleText = $this->blank($episode->itunes_subtitle)
                ? $show->itunes_subtitle
                : $episode->itunes_subtitle;
            $item->appendChild($dom->createElement('itunes:subtitle', $subtitleText));

            // content:encoded — CDATA wrapped, fallback to itunes_description when blank.
            // Episodes get 10,000 bytes (more room for links and show notes).
            $contentEncoded = $this->blank($episode->itunes_content_encoded)
                ? substr($episode->itunes_description, 0, 10000)
                : substr($episode->itunes_content_encoded, 0, 10000);
            $contentEncodedTag = $dom->createElement('content:encoded');
            $contentEncodedTag->appendChild($dom->createCDATASection($contentEncoded));
            $item->appendChild($contentEncodedTag);

            // itunes:author — show-level author on each item, per castfeedvalidator.com.
            $item->appendChild(
                $dom->createElement('itunes:author', $show->itunes_author)
            );
        }
    }


    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Convert a boolean to the lowercase 'yes' or 'no' string required by
     * the Apple Podcasts spec for itunes:explicit, itunes:block, itunes:complete.
     *
     * NOTE: The original package returned 'No'/'yes' (inconsistent case).
     * Apple's spec requires all lowercase. Fixed here.
     */
    private function boolToYesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    /**
     * Return true when a value is null, an empty string, or not set.
     * Used throughout to decide whether optional tags should be emitted.
     */
    private function blank(mixed $value): bool
    {
        if (is_null($value)) return true;
        if ($value === '')   return true;
        if (! isset($value)) return true;

        return false;
    }
}