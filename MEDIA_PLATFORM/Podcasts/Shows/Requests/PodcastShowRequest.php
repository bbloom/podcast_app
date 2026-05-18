<?php

namespace MediaPlatform\Podcasts\Shows\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastShowRequest extends FormRequest
{
    /**
     * Any authenticated user may manage their own podcast shows.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating or updating a podcast show.
     */
    public function rules(): array
    {
        return [
            // Core — required fields
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            // Core — optional fields
            'rss_link' => ['nullable', 'url', 'max:255'],

            // iTunes — optional fields
            'itunes_image'              => ['nullable', 'url', 'max:255'],
            'itunes_language'           => ['nullable', 'string', 'max:10'],
            'itunes_category_primary'   => ['nullable', 'string', 'max:255'],
            'itunes_category_secondary' => ['nullable', 'string', 'max:255'],
            'itunes_explicit'           => ['nullable', 'boolean'],
            'itunes_author'             => ['nullable', 'string', 'max:255'],
            'itunes_link'               => ['nullable', 'url', 'max:255'],
            'itunes_email'              => ['nullable', 'email', 'max:255'],
            'itunes_name'               => ['nullable', 'string', 'max:255'],
            'itunes_title'              => ['nullable', 'string', 'max:255'],
            'itunes_type'               => ['nullable', 'string', 'in:episodic,serial'],
            'itunes_copyright'          => ['nullable', 'string', 'max:255'],
            'itunes_new_feed_url'       => ['nullable', 'url', 'max:255'],
            'itunes_block'              => ['nullable', 'boolean'],
            'itunes_complete'           => ['nullable', 'boolean'],
            'itunes_summary'            => ['nullable', 'string'],
            'itunes_subtitle'           => ['nullable', 'string', 'max:255'],
            'itunes_content_encoded'    => ['nullable', 'string'],

            // Spotify — optional fields
            'spotify_limit'             => ['nullable', 'integer', 'min:0'],
            'spotify_country_of_origin' => ['nullable', 'string', 'max:255'],

            // Website — optional fields
            'website_content'          => ['nullable', 'string'],
            'website_excerpt'          => ['nullable', 'string', 'max:255'],
            'website_meta_description' => ['nullable', 'string', 'max:255'],
            'website_featured_image'   => ['nullable', 'url', 'max:255'],
            'website_publish_on'       => ['nullable', 'date'],
            'website_enabled'          => ['nullable', 'boolean'],

            // Storage — optional fields
            'storage_artwork_url'     => ['nullable', 'url', 'max:65535'],
            'storage_video_files_url' => ['nullable', 'url', 'max:65535'],
            'storage_audio_files_url' => ['nullable', 'url', 'max:65535'],
        ];
    }
}