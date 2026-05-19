<x-layouts.app title="Build Trigger Results — {{ $show->title }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('podcast_shows.show', $show) }}" class="hover:text-purple-700 transition">{{ $show->title }}</a>
        <span>›</span>
        <span class="text-gray-700">Build Trigger Results</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Build Trigger Results</h1>

    {{-- Summary banner --}}
    @php
        $succeeded = collect($results)->filter(fn ($r) => $r->succeeded())->count();
        $failed    = collect($results)->filter(fn ($r) => ! $r->succeeded())->count();
    @endphp

    @if ($failed === 0)
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800 font-medium">
            ✅ All {{ $succeeded }} {{ str('build')->plural($succeeded) }} triggered successfully.
        </div>
    @elseif ($succeeded === 0)
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800 font-medium">
            ❌ All {{ $failed }} {{ str('build')->plural($failed) }} failed to trigger. See details below.
        </div>
    @else
        <div class="bg-amber-50 border border-amber-300 rounded-lg px-4 py-3 mb-6 text-sm text-amber-800 font-medium">
            ⚠ {{ $succeeded }} {{ str('build')->plural($succeeded) }} triggered successfully, {{ $failed }} failed. See details below.
        </div>
    @endif

    {{-- Per-hook results --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Results</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($results as $result)
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">

                            {{-- Hook label + status badge --}}
                            <div class="flex items-center gap-2 mb-1">
                                @if ($result->succeeded())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">Success</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Failed</span>
                                @endif
                                <span class="text-sm font-medium text-gray-800">{{ $result->hook()->label }}</span>
                            </div>

                            {{-- Provider + HTTP status --}}
                            <div class="text-xs text-gray-400 mb-1">
                                {{ $result->hook()->provider->label() }}
                                · HTTP {{ $result->httpStatus() ?: 'no response' }}
                                · {{ now()->format('H:i:s') }}
                            </div>

                            {{-- Build ID (Cloudflare only) --}}
                            @if ($result->buildId())
                                <div class="text-xs text-gray-500">
                                    Build ID:
                                    <span class="font-mono text-purple-700">{{ $result->buildId() }}</span>
                                </div>
                            @endif

                            {{-- Already exists notice --}}
                            @if ($result->alreadyExists())
                                <div class="mt-1 text-xs text-amber-600">
                                    ⚠ A build was already queued or initialising — no new build was created.
                                </div>
                            @endif

                            {{-- Error message --}}
                            @if ($result->errorMessage())
                                <div class="mt-1 text-xs text-red-600">
                                    {{ $result->errorMessage() }}
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Navigation --}}
    <div class="flex items-center gap-4 text-sm">
        <a href="{{ route('podcast_shows.show', $show) }}"
           class="bg-purple-700 hover:bg-purple-800 text-white font-semibold px-5 py-2.5 rounded-lg transition">
            Back to {{ $show->title }}
        </a>
        <a href="{{ route('post_production.trigger_builds.select', $show) }}"
           class="text-gray-500 hover:text-gray-700 transition">
            Trigger again
        </a>
    </div>

</x-layouts.app>