<?php

namespace MediaPlatform\Podcasts\Planning\CRUD\Requests;

use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PodcastEpisodePlanningRequest extends FormRequest
{
    /**
     * All authenticated users may submit this form.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for updating a planning episode.
     *
     * Status is restricted to manual statuses only — wizard-managed statuses
     * (new_episode_created, ready_to_record) cannot be set via this form.
     */
    public function rules(): array
    {
        return [
            // ── Core identity ─────────────────────────────────────────────
            'title'          => ['required', 'string', 'max:255'],
            'episode_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'scheduled_date' => ['nullable', 'date'],

            // ── Status — manual statuses only ─────────────────────────────
            'status'         => [
                'nullable',
                Rule::in(
                    array_map(
                        fn (PodcastEpisodePlanningStatus $s) => $s->value,
                        PodcastEpisodePlanningStatus::manualStatuses()
                    )
                ),
            ],

            // ── Creative content ──────────────────────────────────────────
            'notes'          => ['nullable', 'string'],
            'theme'          => ['nullable', 'string'],
            'script'         => ['nullable', 'string'],

            // ── Website content ───────────────────────────────────────────
            'website_content' => ['nullable', 'string'],
            'website_excerpt' => ['nullable', 'string', 'max:500'],
        ];
    }
}