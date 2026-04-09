<?php

namespace MediaPlatform\API\v1\Services;

use Carbon\CarbonImmutable;
use MediaPlatform\PodcastStudio\Management\Models\PodcastEpisode;
use MediaPlatform\PodcastStudio\Management\Models\PodcastGuest;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;

class PodcastEpisodeApiService
{
    /**
     * Assemble the full API payload:
     *   - All published episodes, each with their guests and links
     *   - All enabled guests (for dedicated guest pages)
     *   - All enabled sponsors
     */
    public function getPayload(): array
    {
        return [
            'episodes' => $this->getEpisodes(),
            'guests'   => $this->getGuests(),
            'sponsors' => $this->getSponsors(),
        ];
    }

    // -------------------------------------------------------------------------
    // Episodes
    // -------------------------------------------------------------------------

    /**
     * Fetch all published episodes, eager-loading guests and links.
     * "Published" means website_enabled = true and website_publish_on is
     * in the past. Ordered newest first.
     */
    private function getEpisodes(): \Illuminate\Support\Collection
    {
        return PodcastEpisode::with(['guests', 'links'])
            ->where('website_enabled', true)
            ->where('website_publish_on', '<', CarbonImmutable::now(config('app.timezone')))
            ->orderBy('website_publish_on', 'desc')
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
}