<x-layouts.app title="Post-Production">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <span class="text-gray-700">Post-Production</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Post-Production</h1>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- ================================================================ --}}
    {{-- PIPELINE                                                          --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Pipeline</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8 space-y-4">

        {{-- Upload Recording to S3 --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.upload_recording.index') }}"
                    class="text-blue-600 hover:underline hover:text-gray-900">Upload Recording to S3</a>
            </div>
        </div>

        {{-- Submit to Auphonic --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.auphonic_processing.index') }}"
                    class="text-blue-600 hover:underline hover:text-gray-900">Submit to Auphonic</a>
            </div>
        </div>

        {{-- Upload Production File to S3 & R2 --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.upload_production_audio.index') }}"
                    class="text-blue-600 hover:underline hover:text-gray-900">Upload the Production Audio File</a>
            </div>
        </div>

        {{-- Generate RSS Feed --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.generate_rss_feed.index') }}"
                    class="text-blue-600 hover:underline hover:text-gray-900">Generate RSS Feed File</a>
            </div>
        </div>

        {{-- Upload RSS Feed to S3 & R2 --}}
        <div class="space-y-2 mb-3">
            <div>
                <span class="text-gray-400">Upload RSS Feed to S3 &amp; R2 <em class="text-xs">(future development)</em></span>
            </div>
        </div>

        {{-- Publish on Website --}}
        <div>
            <a href="{{ route('post_production.publish_on_website.index') }}"
                class="text-blue-600 hover:underline hover:text-gray-900">Publish on Website</a>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- MAINTENANCE                                                       --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Maintenance</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8 space-y-4">

        {{-- Regenerate RSS Feed --}}
        <div>
            <a href="{{ route('post_production.regenerate_rss_feed.index') }}"
                class="text-blue-600 hover:underline hover:text-gray-900">Regenerate RSS Feed</a>
            <p class="mt-1 text-xs text-gray-400">
                Rebuild and republish the RSS feed for any show — independent of the episode pipeline.
            </p>
        </div>

    </div>


</x-layouts.app>