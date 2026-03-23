<x-layouts.app title="Delete List">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('lists.index') }}" class="hover:text-purple-700 transition">My Lists</a>
            <span>›</span>
            <span class="text-gray-700">Delete</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Delete List</h1>
    </div>

    {{-- Warning --}}
    <div class="bg-red-50 border border-red-300 rounded-lg p-5 mb-8">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-1">This cannot be undone</p>
                <p>Deleting this list will permanently remove it and all of its source assignments. Your sources (channels, podcasts, and feeds) will not be deleted — only the list itself.</p>
            </div>
        </div>
    </div>

    {{-- List summary --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg divide-y divide-gray-200 mb-8">
        <div class="px-5 py-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">List Name</p>
            <p class="text-sm font-semibold text-gray-800">{{ $list->name }}</p>
        </div>
        @if ($list->description)
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Description</p>
                <p class="text-sm text-gray-800">{{ $list->description }}</p>
            </div>
        @endif
        <div class="px-5 py-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Schedule</p>
            <p class="text-sm text-gray-800">
                @if ($list->schedule_frequency === 'daily')
                    Daily at {{ $list->schedule_time }}
                @elseif ($list->schedule_frequency === 'weekly')
                    @php $dayNames = ['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday']; @endphp
                    Weekly · {{ $dayNames[$list->schedule_day] ?? '' }} at {{ $list->schedule_time }}
                @elseif ($list->schedule_frequency === 'monthly')
                    Monthly · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }} at {{ $list->schedule_time }}
                @endif
            </p>
        </div>
        <div class="px-5 py-4">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-0.5">Delivery</p>
            <p class="text-sm text-gray-800">{{ $list->output_type === 'webpage' ? 'Web page' : 'Email' }}</p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('lists.edit', $list) }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
            ← Cancel
        </a>
        <form method="POST" action="{{ route('lists.destroy', $list) }}">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Yes, Delete This List
            </button>
        </form>
    </div>

</x-layouts.app>
