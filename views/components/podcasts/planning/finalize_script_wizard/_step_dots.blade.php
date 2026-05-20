@props(['current'])
<div class="flex items-center justify-center gap-2 mb-8 flex-wrap">
    @foreach (range(1, 7) as $step)
        <div class="w-3 h-3 rounded-full {{ $current === $step ? 'bg-purple-700' : ($current > $step ? 'bg-purple-300' : 'bg-gray-300') }}"></div>
        @if ($step < 7)
            <div class="w-6 h-0.5 {{ $current > $step ? 'bg-purple-300' : 'bg-gray-300' }}"></div>
        @endif
    @endforeach
</div>