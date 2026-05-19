<x-layouts.app title="Generate RSS Feed — Step 3">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Generate RSS Feed</h1>
        <a href="{{ route('post_production.generate_rss_feed.step2', $episode) }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Step 2
        </a>
    </div>

    @include('media_platform.podcasts.publishing.post_production.generate_rss_feed._step_dots', ['currentStep' => 3])

    {{-- Episode --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Episode</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-start gap-5">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif
            <table class="text-base text-gray-600 border-collapse w-full">
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-2 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-2 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">RSS File</td>
                    <td class="py-2 text-gray-800 font-mono text-sm">{{ $filename }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Upload result --}}
    @if ($uploadError)

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Staging Upload</div>
        <div class="border border-red-400 rounded-lg px-6 py-4 mb-8">
            <p class="text-sm text-red-700 font-semibold mb-2">The RSS file could not be uploaded to the staging bucket.</p>
            <p class="text-sm text-gray-600 mb-4">{{ $uploadError }}</p>
            <a href="{{ route('post_production.generate_rss_feed.step3', $episode) }}"
               class="inline-block rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Try Again
            </a>
        </div>

    @else

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Staging Upload</div>
        <div class="border border-green-400 rounded-lg px-6 py-4 mb-8">
            <div class="flex items-center gap-3 text-green-700 mb-4">
                <span class="text-lg">✓</span>
                <span class="text-sm font-semibold">RSS feed generated and uploaded to staging successfully.</span>
            </div>
            <p class="text-xs text-gray-500 mb-1">Staging URL — use this in your external validators:</p>
            <div class="flex items-center gap-3">
                <code class="text-xs text-gray-800 bg-gray-100 px-3 py-2 rounded font-mono break-all">{{ $stagingUrl }}</code>
                <button onclick="navigator.clipboard.writeText('{{ $stagingUrl }}')"
                        class="flex-shrink-0 rounded border border-gray-300 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                    Copy
                </button>
            </div>
        </div>

        {{-- Proceed --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('post_production.generate_rss_feed.step4', $episode) }}"
               class="inline-block rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Proceed to External Validation &rarr;
            </a>
        </div>

    @endif

</x-layouts.app>