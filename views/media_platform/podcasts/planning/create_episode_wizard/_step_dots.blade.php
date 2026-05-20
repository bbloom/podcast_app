@props(['current'])
<div class="flex items-center justify-center gap-3 mb-8">
    @foreach ([1, 2, 3, 4] as $step)
        <div class="w-3 h-3 rounded-full {{ $current === $step ? 'bg-purple-700' : ($current > $step ? 'bg-purple-300' : 'bg-gray-300') }}"></div>
        @if ($step < 4)
            <div class="w-8 h-0.5 {{ $current > $step ? 'bg-purple-300' : 'bg-gray-300' }}"></div>
        @endif
    @endforeach
</div>