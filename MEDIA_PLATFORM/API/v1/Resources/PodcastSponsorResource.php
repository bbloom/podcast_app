<?php 

namespace MediaPlatform\API\v1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastSponsorResource extends JsonResource
{
    /**
     * Transform a single sponsor for the Astro build.
     *
     * The three boolean tier flags are collapsed into a single human-readable
     * sponsor_type string — Astro does not need to evaluate booleans.
     *
     * Sensitive fields (email_address, internal_comment) are never included.
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name'              => $this->full_name,
            'image_url'              => $this->image_url,
            'image_thumbnail_url'    => $this->image_thumbnail_url,
            'profile_full'           => $this->profile_full,
            'profile_short'          => $this->profile_short,
            'link_to_sponsor_website'=> $this->link_to_sponsor_website,
            'sponsor_type'           => $this->resolveSponsorType(),
            'former_sponsor'         => $this->former_sponsor,
        ];
    }

    /**
     * Collapse the three boolean tier flags into one readable string.
     */
    private function resolveSponsorType(): string
    {
        if ($this->basecamp_sponsor) return 'Basecamp subscription sponsor';
        if ($this->restream_sponsor) return 'Restream.io subscription sponsor';

        return 'Umbrella sponsor';
    }
}