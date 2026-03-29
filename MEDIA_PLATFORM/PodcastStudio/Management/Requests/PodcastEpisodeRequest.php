<?php

namespace MediaPlatform\PodcastStudio\Management\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastEpisodeRequest extends FormRequest
{
    /**
     * Any authenticated user may manage their own podcast episodes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating or updating a podcast episode.
     */
    public function rules(): array
    {
        return [
            // Relationships — required
            'podcast_show_id'                  => ['required', 'integer', 'exists:podcast_shows,id'],
            'podcast_episode_status_lookup_id' => ['required', 'integer', 'exists:podcast_episode_status_lookup,id'],

            // Core — required
            'title' => ['required', 'string', 'max:255'],

            // Core — optional
            'scheduled_date'           => ['nullable', 'date'],
            'draft'                    => ['nullable', 'string'],
            'raw_input_audio_filename' => ['nullable', 'string', 'max:255'],

            // iTunes — optional
            'itunes_title_tag'        => ['nullable', 'string', 'max:255'],
            'itunes_enclosure_url'    => ['nullable', 'url', 'max:255'],
            'itunes_enclosure_length' => ['nullable', 'string', 'max:255'],
            'itunes_enclosure_type'   => ['nullable', 'string', 'max:255'],
            'itunes_guid'             => ['nullable', 'string', 'max:255'],
            'itunes_pubdate'          => ['nullable', 'date'],
            'itunes_description'      => ['nullable', 'string'],
            'itunes_duration'         => ['nullable', 'string', 'max:255'],
            'itunes_link'             => ['nullable', 'url', 'max:255'],
            'itunes_image'            => ['nullable', 'url', 'max:255'],
            'itunes_explicit'         => ['nullable', 'boolean'],
            'itunes_itunestitle_tag'  => ['nullable', 'string', 'max:255'],
            'itunes_episode'          => ['nullable', 'integer', 'min:0'],
            'itunes_season'           => ['nullable', 'integer', 'min:0'],
            'itunes_episode_type'     => ['nullable', 'string', 'in:full,trailer,bonus'],
            'itunes_block'            => ['nullable', 'boolean'],
            'itunes_summary'          => ['nullable', 'string'],
            'itunes_subtitle'         => ['nullable', 'string', 'max:255'],
            'itunes_content_encoded'  => ['nullable', 'string'],

            // RSS
            'rss_feed_enabled' => ['nullable', 'boolean'],

            // Website — optional
            'website_content'          => ['nullable', 'string'],
            'website_excerpt'          => ['nullable', 'string', 'max:255'],
            'website_meta_description' => ['nullable', 'string', 'max:255'],
            'website_episode_notes'    => ['nullable', 'string'],
            'website_attribution'      => ['nullable', 'string'],
            'website_featured_image'   => ['nullable', 'url', 'max:255'],
            'website_publish_on'       => ['nullable', 'date'],
            'website_enabled'          => ['nullable', 'boolean'],
        ];
    }
}