<x-layouts.app title="Post-Production">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <span class="text-gray-700">Post-Production</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Post-Production</h1>

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
        <a href="{{ route('post_production.generate_rss_feed.index') }}"
            class="text-blue-600 hover:underline hover:text-gray-900">Generate RSS Feed File</a>

        {{-- Upload RSS Feed to S3 & R2 --}}
        <div>
            <span class="text-gray-400">Upload RSS Feed to S3 &amp; R2 <em class="text-xs">(future development)</em></span>
        </div>

        {{-- Publish on Website --}}
        <div>
            <span class="text-gray-400">Publish on Website <em class="text-xs">(future development)</em></span>
        </div>

    </div>

</x-layouts.app>