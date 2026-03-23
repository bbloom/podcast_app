<?php

namespace MediaPlatform\Configuration\LanguageModels\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $modelId = $this->route('language_model')?->id;

        return [
            'provider_id'    => ['required', 'integer', Rule::exists('providers', 'id')],
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('language_models', 'slug')->ignore($modelId)],
            'description'    => ['nullable', 'string'],
            'enabled'        => ['boolean'],
            'use_case_ids'   => ['nullable', 'array'],
            'use_case_ids.*' => ['integer', Rule::exists('use_cases', 'id')],
        ];
    }

    /**
     * Force enabled = true when the model holds the digest-processing use case.
     *
     * This runs after validation so the cast to boolean has already happened.
     * We check the *current* attached use cases (before any sync), because the
     * edit form does not change use case attachments — that happens via the
     * dedicated attach/detach routes.
     */
    /**
     * Override validated() to force enabled = true when the model holds
     * digest-processing, regardless of what the form submitted.
     */
    public function validated($key = null, $default = null): array
    {
        $data  = parent::validated($key, $default);
        $model = $this->route('language_model');

        if ($model && $model->useCases()->where('slug', 'digest-processing')->exists()) {
            $data['enabled'] = true;
        }

        return $data;
    }
}