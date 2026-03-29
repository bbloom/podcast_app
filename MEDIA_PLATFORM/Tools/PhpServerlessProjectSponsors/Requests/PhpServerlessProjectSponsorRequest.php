<?php

namespace MediaPlatform\Tools\PhpServerlessProjectSponsors\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhpServerlessProjectSponsorRequest extends FormRequest
{
    /**
     * Only authenticated users may create or update sponsors.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for creating and updating a sponsor.
     */
    public function rules(): array
    {
        // On update, ignore the current record's own full_name for uniqueness.
        $uniqueRule = 'unique:phpserverlessproject_sponsors,full_name';
        if ($this->route('phpserverlessproject_sponsor')) {
            $uniqueRule .= ',' . $this->route('phpserverlessproject_sponsor')->id;
        }

        return [
            'full_name'               => ['required', 'string', 'max:255', $uniqueRule],
            'image_url'               => ['nullable', 'url', 'max:255'],
            'image_thumbnail_url'     => ['nullable', 'url', 'max:255'],
            'profile_full'            => ['required', 'string'],
            'profile_short'           => ['nullable', 'string', 'max:255'],
            'link_to_sponsor_website' => ['nullable', 'url', 'max:255'],
            'email_address'           => ['required', 'email', 'max:255'],
            'umbrella_sponsor'        => ['required', 'boolean'],
            'basecamp_sponsor'        => ['required', 'boolean'],
            'restream_sponsor'        => ['required', 'boolean'],
            'former_sponsor'          => ['required', 'boolean'],
            'internal_comment'        => ['nullable', 'string'],
            'enabled'                 => ['required', 'boolean'],
        ];
    }
}