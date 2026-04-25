<?php

namespace MediaPlatform\API\v1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastShowResource extends JsonResource
{
    /**
     * Transform a single podcast show for the Astro build.
     *
     * Footer links are embedded in full — they are show-specific and small.
     * Ordered by link_order ascending.
     *
     * Internal, storage, and sensitive fields are deliberately excluded.
     */
    public function toArray(Request $request): array
    {
        return [
            'title'            => $this->title,
            'description'      => $this->description,
            'itunes_image'     => $this->itunes_image,
            'itunes_copyright' => $this->itunes_copyright,
            'website_url'      => $this->itunes_link,
            'feed_url'         => $this->rss_link,

            // Footer links: embedded in full — small and show-specific.
            'footer_links' => $this->footerLinks
                ->sortBy('link_order')
                ->values()
                ->map(fn ($link) => [
                    'link_name'  => $link->link_name,
                    'link_url'   => $link->link_url,
                    'link_order' => $link->link_order,
                ]),
        ];
    }
}