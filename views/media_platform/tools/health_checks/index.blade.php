<x-layouts.app title="Health Checks">

    <div class="mb-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Health Checks</h1>
            <a href="{{ route('admin.health-checks.readme') }}"
               class="text-sm text-purple-700 hover:text-purple-800 font-semibold transition">
                Reference Guide →
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-1">System health alerts and processing status.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-300 rounded-lg px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Unresolved alerts --}}
    <div class="mb-10">
        <h2 class="text-lg font-bold text-gray-800 mb-4">
            Unresolved Alerts
            @if ($unresolvedAlerts->isNotEmpty())
                <span class="text-sm font-normal text-gray-500 ml-2">({{ $unresolvedAlerts->count() }})</span>
            @endif
        </h2>

        @if ($unresolvedAlerts->isEmpty())
            <div class="bg-green-50 border border-green-200 rounded-lg px-5 py-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm text-green-800 font-semibold">All systems healthy. No unresolved alerts.</p>
                </div>
            </div>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($unresolvedAlerts as $alert)
                    <div class="border rounded-lg px-5 py-4 {{ $alert->tier === 3 ? 'border-red-300 bg-red-50/50' : 'border-amber-300 bg-amber-50/50' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    {{-- Tier badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                        {{ $alert->tier === 3 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                        Tier {{ $alert->tier }}
                                    </span>
                                    {{-- Category badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">
                                        {{ $alert->category }}
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-gray-800 mt-1">{{ $alert->title }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $alert->message }}</p>
                                <p class="text-xs text-gray-400 mt-2">
                                    Detected {{ $alert->created_at->diffForHumans() }}
                                    @if ($alert->notified_at)
                                        · Email sent {{ $alert->notified_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>

                            @if ($alert->tier === 3)
                                <form method="POST" action="{{ route('admin.health-checks.resolve', $alert) }}" class="flex-shrink-0">
                                    @csrf
                                    <button
                                        type="submit"
                                        onclick="return confirm('Have you fixed the underlying issue? Marking as resolved will unblock processing for this subsystem.')"
                                        class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-lg transition"
                                    >
                                        Mark Resolved
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 flex-shrink-0 mt-1">Auto-resolves</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recently resolved --}}
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Recently Resolved</h2>

        @if ($resolvedAlerts->isEmpty())
            <p class="text-sm text-gray-500">No resolved alerts yet.</p>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($resolvedAlerts as $alert)
                    <div class="border border-gray-200 rounded-lg px-5 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">
                                        Resolved
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-500">
                                        {{ $alert->category }}
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-gray-600 mt-1">{{ $alert->title }}</p>
                            </div>
                            <p class="text-xs text-gray-400 flex-shrink-0">
                                {{ $alert->resolved_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.app>
