{{--
    Wizard step dots for the Lists wizard.

    Usage: @include('lists.lists._step_dots', ['current' => 1, 'outputType' => 'webpage'])

    Webpage flow: steps 1–6  (step 4 = destination, step 5 = notifications)
    Email flow:   steps 1–3 then jumps to step 6 (steps 4+5 are skipped)

    We always render 6 dots. Skipped dots (4 & 5 for email) are shown dimmed.
--}}

@php
    $isEmail   = ($outputType ?? null) === 'email';
    $totalDots = 6;
@endphp

<div class="flex items-center gap-1.5 mt-3">
    @for ($i = 1; $i <= $totalDots; $i++)
        @php
            $skipped = $isEmail && in_array($i, [4, 5]);
            $done    = $i < $current && ! $skipped;
            $active  = $i === $current;
        @endphp

        @if ($active)
            <span class="w-3 h-3 rounded-full bg-purple-700 inline-block"></span>
        @elseif ($done)
            <span class="w-3 h-3 rounded-full bg-purple-300 inline-block"></span>
        @elseif ($skipped)
            <span class="w-3 h-3 rounded-full bg-gray-200 inline-block opacity-40"></span>
        @else
            <span class="w-3 h-3 rounded-full bg-gray-200 inline-block"></span>
        @endif
    @endfor
</div>
