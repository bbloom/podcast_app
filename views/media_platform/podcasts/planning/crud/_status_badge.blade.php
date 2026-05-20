@php
    use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
    $s = $status instanceof PodcastEpisodePlanningStatus
        ? $status
        : PodcastEpisodePlanningStatus::tryFrom($status);
@endphp

<span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $s?->cssClass() ?? 'bg-gray-100 text-gray-700' }}">
    {{ $s?->label() ?? $status }}
</span>