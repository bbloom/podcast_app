<?php 
namespace MediaPlatform\API\v1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastGuestResource extends JsonResource
{
    /**
     * Transform a single podcast guest for the Astro build.
     *
     * The slug is the key Astro uses to build guest pages and to cross-reference
     * guest slugs embedded in each episode's guests array.
     *
     * Sensitive fields (email_address, internal_comment) are never included.
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name'             => $this->full_name,
            'slug'                  => $this->slug,
            'image_url'             => $this->image_url,
            'image_thumbnail_url'   => $this->image_thumbnail_url,
            'profile_full'          => $this->profile_full,
            'profile_short'         => $this->profile_short,
            'link_to_guest_website' => $this->link_to_guest_website,
        ];
    }
}