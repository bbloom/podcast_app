<x-layouts.app title="Trigger Static Site Builds — {{ $show->title }}">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        @if ($context === 'publish')
            <a href="{{ route('post_production.publish_on_website.index') }}" class="hover:text-purple-700 transition">Publish on Website</a>
        @else
            <a href="{{ route('podcast_shows.show', $show) }}" class="hover:text-purple-700 transition">{{ $show->title }}</a>
        @endif
        <span>›</span>
        <span class="text-gray-700">Trigger Static Site Builds</span>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Trigger Static Site Builds</h1>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Show --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
        <!-- 
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Show</h2>
        </div>
        -->
        <div class="px-4 py-3 text-sm text-gray-800">
            <img 
                src="{{ $show->itunes_image }}" 
                alt="" 
                class="w-[100px] h-[100px] rounded-lg object-cover border border-gray-200 flex-shrink-0"
            >
        </div>
    </div>

    {{-- Hook selection --}}
    <form method="POST" action="{{ route('post_production.trigger_builds.trigger', $show) }}">
        @csrf

        <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-3 bg-purple-50 border-b border-purple-300">
                <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Select Builds to Trigger</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($hooks as $hook)
                    <label class="flex items-center gap-4 px-4 py-4 hover:bg-gray-50 cursor-pointer transition">
                        <input type="checkbox"
                               name="hook_ids[]"
                               value="{{ $hook->id }}"
                               checked
                               class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-800">{{ $hook->label }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $hook->provider->label() }}
                                @if ($hook->last_triggered_at)
                                    · Last triggered {{ $hook->last_triggered_at->diffForHumans() }}
                                    @if ($hook->last_trigger_status === 'failed')
                                        · <span class="text-red-500 font-medium">Last trigger failed</span>
                                    @endif
                                @else
                                    · Never triggered
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Trigger Selected Builds
            </button>
            <a href="{{ $context === 'publish' ? route('post_production.publish_on_website.index') : route('podcast_shows.show', $show) }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">
                Skip for now
            </a>
        </div>

    </form>

</x-layouts.app>