<?php

namespace MediaPlatform\PodcastStudio\Management\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastGuestRequest extends FormRequest
{
    /**
     * Only authenticated users may create or update guests.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for creating and updating a podcast guest.
     */
    public function rules(): array
    {
        // On update, ignore the current record's own full_name for uniqueness.
        $uniqueRule = 'unique:podcast_guests,full_name';
        if ($this->route('podcast_guest')) {
            $uniqueRule .= ',' . $this->route('podcast_guest')->id;
        }

        return [
            'full_name'             => ['required', 'string', 'max:255', $uniqueRule],
            'image_url'             => ['nullable', 'url', 'max:255'],
            'image_thumbnail_url'   => ['nullable', 'url', 'max:255'],
            'profile_full'          => ['required', 'string'],
            'profile_short'         => ['nullable', 'string', 'max:255'],
            'link_to_guest_website' => ['nullable', 'url', 'max:255'],
            'email_address'         => ['required', 'email', 'max:255'],
            'internal_comment'      => ['nullable', 'string'],
            'enabled'               => ['required', 'boolean'],
        ];
    }
}