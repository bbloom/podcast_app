{{--
    Wizard step dots for the Create Episode wizard.

    Usage: @include('media_platform.podcast_studio.pre_production.create_episode._step_dots', ['current' => 1])

    2 steps:
        1 - Select show
        2 - Episode details (number, title, date, website content)
--}}

<div class="flex items-center gap-1.5 mt-3">
    @for ($i = 1; $i <= 2; $i++)
        @if ($i === $current)
            <span class="w-3 h-3 rounded-full bg-purple-700 inline-block"></span>
        @elseif ($i < $current)
            <span class="w-3 h-3 rounded-full bg-purple-300 inline-block"></span>
        @else
            <span class="w-3 h-3 rounded-full bg-gray-200 inline-block"></span>
        @endif
    @endfor
</div>