<x-layouts.app title="Generate RSS Feed — Step 3 — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Generate RSS Feed — Step 3</h1>
        <a href="{{ route('post_production.generate_rss_feed.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to RSS Feed Generation
        </a>
    </div>

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
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-1 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-1 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if ($uploadError)

        {{-- Upload failed --}}
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

        {{-- Success --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">RSS Feed Generated</div>
        <div class="border border-green-400 rounded-lg px-6 py-4 mb-8">
            <div class="flex items-center gap-2 text-green-700 mb-3">
                <span class="text-lg">✓</span>
                <span class="text-sm font-semibold">RSS XML generated and staged successfully.</span>
            </div>
            <table class="text-sm text-gray-600 w-full">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap w-32">File</td>
                    <td class="py-1 text-gray-800 font-mono text-xs">{{ $filename }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Staging URL</td>
                    <td class="py-1">
                        <div class="flex items-center gap-2">
                            <code class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded font-mono break-all flex-1">{{ $stagingUrl }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $stagingUrl }}')"
                                    class="flex-shrink-0 rounded border border-gray-300 px-2 py-1 text-xs text-gray-500 hover:bg-gray-50 transition-colors">
                                Copy
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Internal staging URL — validation happens after promoting to live S3 in the next step.</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Promote to live S3 --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Promote to Live S3</div>
        <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">
            <p class="text-sm text-gray-600 mb-6">
                Upload the feed to the live S3 bucket. You will then validate it against the live
                S3 URL — the same URL that podcast directories and validators will use — before
                it is promoted to Cloudflare R2.
            </p>
            <form method="POST"
                  action="{{ route('post_production.generate_rss_feed.step5', $episode) }}">
                @csrf
                <button type="submit"
                        class="rounded bg-purple-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                    Upload to Live S3 &rarr;
                </button>
            </form>
        </div>

    @endif

    {{-- Back --}}
    <div class="text-sm">
        <a href="{{ route('post_production.generate_rss_feed.step2', $episode) }}"
           class="text-purple-700 hover:underline">
            &larr; Back to Step 2
        </a>
    </div>

</x-layouts.app>