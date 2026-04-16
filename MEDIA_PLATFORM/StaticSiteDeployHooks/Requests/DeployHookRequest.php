<?php

// =============================================================================
// DeployHookRequest
//
// Validates create and update requests for deploy hooks.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Requests/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;

class DeployHookRequest extends FormRequest
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
     * Validation rules for creating and updating a deploy hook.
     */
    public function rules(): array
    {
        // On update, the URL may be left blank to keep the existing encrypted
        // value — it cannot be displayed so the user can only replace it, not
        // edit it in place. On create, a URL is always required.
        $urlRule = $this->isMethod('PUT')
            ? ['nullable', 'url', 'max:2048']
            : ['required', 'url', 'max:2048'];

        return [
            'triggerable_type' => ['required', 'string'],
            'triggerable_id'   => ['required', 'integer'],
            'label'            => ['required', 'string', 'max:255'],
            'provider'         => ['required', 'string', Rule::enum(DeployHookProvider::class)],
            'url'              => $urlRule,
            'enabled'          => ['boolean'],
        ];
    }
}