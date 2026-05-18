<?php

namespace MediaPlatform\Podcasts\Links\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PodcastLinkRequest extends FormRequest
{
    /**
     * Only authenticated users may create or update links.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for creating and updating a podcast link.
     */
    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'link'        => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'comments'    => ['nullable', 'string'],
        ];
    }
}