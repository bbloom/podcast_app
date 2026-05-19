<?php

namespace MediaPlatform\API\v1\Resources;

use MediaPlatform\Podcasts\ArchivedEpisodes\BobBloomShowArchive;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastEpisodeResource extends JsonResource
{
    /**
     * Transform a single podcast episode for the Astro build.
     *
     * Guests are represented as an array of slugs only — Astro cross-references
     * the slug against the top-level guests array to get the full profile.
     *
     * Links are embedded in full — they are episode-specific and small.
     *
     * Internal, RSS-only, and sensitive fields are deliberately excluded.
     */
    public function toArray(Request $request): array
    {
        return [
            // Core
            'title'                    => $this->title,
            'slug'                     => $this->slug,
            'website_publish_on'       => $this->website_publish_on,

            // Website content
            'website_content'          => $this->website_content,
            'website_excerpt'          => $this->website_excerpt,
            'website_meta_description' => $this->website_meta_description,
            'website_episode_notes'    => $this->website_episode_notes,
            'website_attribution'      => $this->formatAttribution($this->website_attribution),
            'website_featured_image'   => $this->website_featured_image,

            // iTunes / audio
            'itunes_enclosure_url'     => $this->itunes_enclosure_url,
            'itunes_image'             => $this->itunes_image,
            'itunes_pubdate'           => $this->itunes_pubdate,
            'itunes_duration' => $this->itunes_duration
                ? '(' . preg_replace('/^00?:/', '', $this->itunes_duration) . ')'
                : null,
            'itunes_episode'           => $this->itunes_episode,
            'itunes_season'            => $this->itunes_season,
            'itunes_episode_type'      => $this->itunes_episode_type,
            'itunes_summary'           => $this->itunes_summary,

            // Relationships
            // Guests: array of slugs only — Astro resolves full profile from
            // the top-level guests array using these slugs as keys.
            'guests' => $this->guests
                ->where('enabled', true)
                ->pluck('slug')
                ->values(),

            // Links: embedded in full — small and episode-specific.
            'links' => $this->links
                ->where('enabled', true)
                ->map(fn ($link) => [
                    'title' => $link->title,
                    'link'  => $link->link,
                    // 'description' => $link->description,  // not displaying the link's description in the front-ends
                ])
                ->values(),
        ];
    }


    // -------------------------------------------------------------------------
    // Attribution formatting
    // -------------------------------------------------------------------------

    /**
     * Format the website_attribution HTML for Astro rendering.
     *
     * Transformations applied:
     *   - <div> sub-headings become bold black text
     *   - <ul> gets list styling
     *   - <li> gets bullet styling and black text
     *   - <a> links get hover:underline and open in a new tab
     */
    private function formatAttribution(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        // Bold black sub-headings
        $content = str_replace(
            '<div>',
            '<div class="font-bold text-black mt-3">',
            $content
        );

        // List styling
        $content = str_replace(
            '<ul>',
            '<ul class="list-disc list-inside mt-1 break-words">',
            $content
        );

        // List item styling — black text, comfortable padding
        $content = str_replace(
            '<li>',
            '<li class="text-black pl-4 mt-1">',
            $content
        );

        // Links — black, hover underline, open in new tab
        $content = str_replace(
            '<a href',
            '<a class="text-black hover:underline" href',
            $content
        );

        return $content;
    }


    // -------------------------------------------------------------------------
    // Bob Bloom Show archive transformation
    // -------------------------------------------------------------------------

    /**
     * Transform the raw BobBloomShowArchive episodes array into a consistently
     * shaped collection for the API payload.
     */
    public static function transformBobBloomArchive(array $episodes): array
    {
        $baseUrl = BobBloomShowArchive::LOCATION_URL;

        return array_map(function (array $ep) use ($baseUrl) {
            return [
                'episode_number' => $ep['episode_number'],
                'title'          => $ep['title'],
                'date'           => $ep['date'],
                'duration'       => $ep['duration'],
                'audio_url'      => $baseUrl . $ep['filename'],
            ];
        }, $episodes);
    }
}