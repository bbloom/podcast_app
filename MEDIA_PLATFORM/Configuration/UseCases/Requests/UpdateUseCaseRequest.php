<?php

namespace MediaPlatform\Configuration\UseCases\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUseCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route parameter is {use_case} (snake_case), not {useCase}.
        $useCaseId = $this->route('use_case')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('use_cases', 'slug')->ignore($useCaseId)],
            'description' => ['nullable', 'string'],
        ];
    }
}