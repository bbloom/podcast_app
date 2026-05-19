<?php

namespace MediaPlatform\API\v1\Services;

use MediaPlatform\Podcasts\Publishing\Models\PodcastEpisode;
use MediaPlatform\Podcasts\Guests\Models\PodcastGuest;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\Podcasts\ArchivedEpisodes\BobBloomShowArchive;
use MediaPlatform\API\v1\Resources\PodcastEpisodeResource;


class PodcastEpisodeApiService
{
    /**
     * Assemble the full API payload:
     *   - The podcast show itself (with footer links)
     *   - Published episodes for the requested show, each with their guests and links
     *   - All enabled guests (for dedicated guest pages)
     *   - All enabled sponsors
     *   - The 57 original Bob Bloom Show archived episodes (bob-bloom-show only)
     */
    public function getPayload(string $podcastShowSlug): array
    {
        $show = PodcastShow::where('slug', $podcastShowSlug)
            ->with('footerLinks')
            ->first();

        $payload = [
            'show'     => $show,
            'episodes' => $this->getEpisodes($show),
            'guests'   => $this->getGuests(),
            'sponsors' => $this->getSponsors(),
        ];

        if ($podcastShowSlug == 'bob-bloom-show') {
            $payload['bob_bloom_archive'] = $this->getBobBloomArchive();
        }

        return $payload;
    }

    // -------------------------------------------------------------------------
    // Episodes
    // -------------------------------------------------------------------------

    /**
     * Fetch published episodes for the given show, eager-loading guests and links.
     * "Published" means website_enabled = true and website_publish_on is
     * in the past. Ordered newest first.
     */
    private function getEpisodes(?PodcastShow $show): \Illuminate\Support\Collection
    {
        // Return an empty collection if the show does not exist —
        // consistent with the original behaviour of whereHas() returning nothing.
        if (! $show) {
            return collect();
        }

        return PodcastEpisode::eligibleForPublishOnWebsite($show)
            ->with(['guests', 'links'])
            ->get();
    }

    // -------------------------------------------------------------------------
    // Guests
    // -------------------------------------------------------------------------

    /**
     * Fetch all enabled guests for the top-level guests array.
     * These are used by Astro to build dedicated guest pages.
     */
    private function getGuests(): \Illuminate\Support\Collection
    {
        return PodcastGuest::where('enabled', true)
            ->orderBy('full_name', 'asc')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Sponsors
    // -------------------------------------------------------------------------

    /**
     * Fetch all enabled sponsors, ordered by name.
     */
    private function getSponsors(): \Illuminate\Support\Collection
    {
        return PhpServerlessProjectSponsor::where('enabled', true)
            ->orderBy('full_name', 'asc')
            ->get();
    }

    // -------------------------------------------------------------------------
    // 57 original Bob Bloom Show episodes
    // -------------------------------------------------------------------------

    private function getBobBloomArchive(): array
    {
        $archive = new BobBloomShowArchive();

        return PodcastEpisodeResource::transformBobBloomArchive($archive->episodes());
    }
}