<?php

namespace MediaPlatform\Configuration\UseCases\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUseCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('use_cases', 'slug')],
            'description' => ['nullable', 'string'],
        ];
    }
}
