{{--
    Wizard step dots for the Draft Pre-Production wizard.

    Usage: @include('...pre_production._step_dots', ['current' => 1])

    4 steps:
        1 - Select draft
        2 - Finalize title, episode number, date
        3 - Finalize draft/script
        4 - Finalize website content
--}}

<div class="flex items-center gap-1.5 mt-3">
    @for ($i = 1; $i <= 4; $i++)
        @if ($i === $current)
            <span class="w-3 h-3 rounded-full bg-purple-700 inline-block"></span>
        @elseif ($i < $current)
            <span class="w-3 h-3 rounded-full bg-purple-300 inline-block"></span>
        @else
            <span class="w-3 h-3 rounded-full bg-gray-200 inline-block"></span>
        @endif
    @endfor
</div>