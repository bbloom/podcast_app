<?php

namespace MediaPlatform\Tools\FooterLinks\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FooterLinkRequest extends FormRequest
{
    /**
     * Always return true — access control is handled in the controller
     * via ownership checks. FormRequest::authorize() cannot redirect.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating and updating a footer link.
     */
    public function rules(): array
    {
        return [
            'podcast_show_id' => ['required', 'integer', 'exists:podcast_shows,id'],
            'link_name'       => ['required', 'string', 'max:255'],
            'link_url'        => ['required', 'url', 'max:2048'],
            'link_order'      => ['required', 'integer', 'min:0'],
        ];
    }
}