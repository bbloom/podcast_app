<x-layouts.app title="My Lists">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">My Lists</h1>
        <a href="{{ route('lists.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New List
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($lists->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No lists yet</p>
            <p class="text-sm text-gray-400 mb-6">Create your first list to start building digests.</p>
            <a href="{{ route('lists.create.step1') }}" class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create a List
            </a>
        </div>

    @else

        <div class="flex flex-col gap-3">
            @foreach ($lists as $list)
                <div class="border border-gray-200 rounded-lg px-5 py-4 flex items-center justify-between hover:border-gray-300 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800 truncate">({{ $list->id }}) {{ $list->name }}</p>
                            @if (! $list->enabled)
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Disabled</span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500">
                            {{-- Schedule summary --}}
                            @if ($list->schedule_frequency === 'daily')
                                Daily at {{ $list->schedule_time }}
                            @elseif ($list->schedule_frequency === 'weekly')
                                @php
                                    $dayNames = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun'];
                                @endphp
                                Weekly · {{ $dayNames[$list->schedule_day] ?? '' }} at {{ $list->schedule_time }}
                            @elseif ($list->schedule_frequency === 'monthly')
                                Monthly · {{ $list->schedule_day }}{{ match(true) { $list->schedule_day==1||$list->schedule_day==21||$list->schedule_day==31=>'st', $list->schedule_day==2||$list->schedule_day==22=>'nd', $list->schedule_day==3||$list->schedule_day==23=>'rd', default=>'th' } }} at {{ $list->schedule_time }}
                            @endif

                            <span class="mx-1.5">·</span>

                            @if ($list->output_type->value === 'email')
                                Email
                            @elseif ($list->output_type->value === 'wordpress')
                                WordPress
                            @else
                                Web page
                            @endif

                            {{-- Timezone if overridden --}}
                            @if ($list->timezone)
                                <span class="mx-1.5">·</span>{{ $list->timezone }}
                            @endif

                            {{-- Last run --}}
                            @if ($list->last_run_at)
                                <span class="mx-1.5">·</span>Last run {{ $list->last_run_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                        <a href="{{ route('lists.show', $list) }}"
                            class="text-xs text-gray-500 hover:text-purple-700 font-medium transition">
                            Details
                        </a>
                        <a href="{{ route('lists.delete.confirm', $list) }}"
                           class="text-xs text-gray-400 hover:text-red-600 font-medium transition">
                            Delete
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

</x-layouts.app>
