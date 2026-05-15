<?php

// =============================================================================
// PodcastEpisodeDraftRequest
//
// Validation rules for creating or updating a podcast episode draft.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PodcastEpisodeDrafts/Requests/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PodcastEpisodeDrafts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastEpisodeDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'podcast_show_id' => ['required', 'integer', 'exists:podcast_shows,id'],
            'title'           => ['required', 'string', 'max:255'],
            'date'            => ['nullable', 'date'],
            'episode_number'  => ['nullable', 'integer', 'min:1'],
            'draft'           => ['nullable', 'string'],
            'website_content' => ['nullable', 'string', 'max:10000'],
            'website_excerpt' => ['nullable', 'string', 'max:255'],
            'guest_notes'     => ['nullable', 'string', 'max:255'],
            'comments'        => ['nullable', 'string', 'max:255'],
            'basecamp_url'    => ['nullable', 'url', 'max:255'],
        ];
    }
}