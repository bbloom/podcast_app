<?php

namespace MediaPlatform\Videos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use MediaPlatform\Videos\Enums\VideoStatus;

/**
 * Validation rules for creating and updating a video via the edit form.
 */
class VideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the video edit/update form.
     */
    public function rules(): array
    {
        $videoId = $this->route('video')?->id;

        return [
            'title'               => ['required', 'string', 'max:255'],
            'slug'                => ['required', 'string', 'max:255', 'unique:videos,slug,' . $videoId],
            'description'         => ['required', 'string', 'max:5000'],
            'scheduled_date'      => ['required', 'date'],
            'status'              => ['required', 'string', 'in:' . implode(',', array_column(VideoStatus::cases(), 'value'))],
            'youtube_title'       => ['nullable', 'string', 'max:255'],
            'youtube_description' => ['nullable', 'string', 'max:5000'],
            'youtube_chapters'    => ['nullable', 'string', 'max:5000'],
            'youtube_url'         => ['nullable', 'string', 'max:255', 'url'],
        ];
    }
}