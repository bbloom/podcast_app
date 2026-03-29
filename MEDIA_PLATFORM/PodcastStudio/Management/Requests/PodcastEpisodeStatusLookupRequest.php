<?php

namespace MediaPlatform\PodcastStudio\Management\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastEpisodeStatusLookupRequest extends FormRequest
{
    /**
     * Only admins may create or update status lookup records.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * Validation rules for creating or updating a status lookup record.
     */
    public function rules(): array
    {
        // When updating, exclude the current record from the unique check.
        $uniqueRule = 'unique:podcast_episode_status_lookup,title';
        if ($this->route('podcast_episode_status_lookup')) {
            $uniqueRule .= ',' . $this->route('podcast_episode_status_lookup')->id;
        }

        return [
            'title'       => ['required', 'string', 'max:255', $uniqueRule],
            'description' => ['required', 'string', 'max:255'],
            'enabled'     => ['required', 'boolean'],
        ];
    }
}