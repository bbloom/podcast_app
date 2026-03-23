<?php

namespace MediaPlatform\Configuration\LanguageModels\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLanguageModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id'  => ['required', 'integer', Rule::exists('providers', 'id')],
            'name'         => ['required', 'string', 'max:255'],
            'slug'         => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('language_models', 'slug')],
            'description'  => ['nullable', 'string'],
            'enabled'    => ['boolean'],
            'use_case_ids' => ['nullable', 'array'],
            'use_case_ids.*' => ['integer', Rule::exists('use_cases', 'id')],
        ];
    }
}
