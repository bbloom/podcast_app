@php
    use MediaPlatform\Podcasts\Planning\CRUD\Enums\PodcastEpisodePlanningStatus;
    $s = $status instanceof PodcastEpisodePlanningStatus
        ? $status
        : PodcastEpisodePlanningStatus::tryFrom($status);
@endphp

<span class="inline-block px-10 py-4 rounded-full text-base font-bold {{ $s?->cssClass() ?? 'bg-gray-100 text-black' }}">
    {{ $s?->label() ?? $status }}
</span>