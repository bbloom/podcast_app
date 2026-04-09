<?php

namespace MediaPlatform\API\v1\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiClientRequest extends FormRequest
{
    /**
     * Always return true — access control is handled in the controller
     * via denyIfNotAdmin(), which redirects gracefully instead of
     * throwing a 403. FormRequest::authorize() cannot redirect.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating and updating an API client.
     */
    public function rules(): array
    {
        // On update, ignore the current record's own domain for uniqueness.
        $uniqueRule = 'unique:api_clients,domain';
        if ($this->route('api_client')) {
            $uniqueRule .= ',' . $this->route('api_client')->id;
        }

        return [
            'label'     => ['required', 'string', 'max:255'],
            'domain'    => ['required', 'string', 'max:255', $uniqueRule],
            'is_active' => ['required', 'boolean'],
        ];
    }
}