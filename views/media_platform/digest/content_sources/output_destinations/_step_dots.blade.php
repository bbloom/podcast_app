{{--
    Output Destination Wizard — Step Dots
    Usage: @include('lists.output_destinations._step_dots', ['current' => 3])
--}}
<div class="flex items-center gap-1 mt-3">
    @for ($i = 1; $i <= 8; $i++)
        <div class="w-2 h-2 rounded-full {{ $i < $current ? 'bg-purple-300' : ($i === $current ? 'bg-purple-700' : 'bg-gray-300') }}"></div>
        @if ($i < 8)
            <div class="w-8 h-px {{ $i < $current ? 'bg-purple-300' : 'bg-gray-300' }}"></div>
        @endif
    @endfor
</div>
