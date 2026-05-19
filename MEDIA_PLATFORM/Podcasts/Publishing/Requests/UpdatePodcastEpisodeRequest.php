<?php

namespace MediaPlatform\Podcasts\Publishing\Requests;


use MediaPlatform\Podcasts\Enums\PodcastEpisodeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;


class UpdatePodcastEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a podcast episode.
     *
     * Every field in the edit form is listed here explicitly.
     * If a field is in the form but missing from these rules,
     * it will be silently dropped by $request->validated().
     */
    public function rules(): array
    {
        return [
            // -----------------------------------------------------------------
            // General — required
            // -----------------------------------------------------------------
            'podcast_show_id' => ['required', 'integer', 'exists:podcast_shows,id'],
            'title'           => ['required', 'string', 'max:255'],

            // -----------------------------------------------------------------
            // General — optional
            // -----------------------------------------------------------------
            'slug'                     => ['nullable', 'string', 'max:255'],
            'scheduled_date'           => ['nullable', 'date'],
            'draft'                    => ['nullable', 'string'],
            'raw_input_audio_filename' => ['nullable', 'string', 'max:255'],

            // -----------------------------------------------------------------
            // Status
            // -----------------------------------------------------------------
            'status' => ['required', new Enum(PodcastEpisodeStatus::class)],

            // -----------------------------------------------------------------
            // iTunes
            // -----------------------------------------------------------------
            'itunes_title_tag'        => ['nullable', 'string', 'max:255'],
            'itunes_enclosure_url'    => ['nullable', 'url', 'max:255'],
            'itunes_enclosure_length' => ['nullable', 'string', 'max:255'],
            'itunes_enclosure_type'   => ['nullable', 'string', 'in:audio/x-m4a,audio/mpeg,video/quicktime,video/mp4,video/x-m4v,application/pdf'],
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

            // -----------------------------------------------------------------
            // RSS
            // -----------------------------------------------------------------
            'rss_feed_enabled' => ['nullable', 'boolean'],

            // -----------------------------------------------------------------
            // Website
            // -----------------------------------------------------------------
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