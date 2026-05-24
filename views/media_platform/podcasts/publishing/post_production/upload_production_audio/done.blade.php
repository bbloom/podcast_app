<x-layouts.app title="Production Audio Uploaded — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Production Audio Uploaded</h1>
        <a href="{{ route('post_production.dashboard') }}"
           class="text-sm text-purple-700 hover:underline">
            Post-Production Dashboard
        </a>
    </div>

    @session('warning')
        <div class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-base text-yellow-800">
            <strong>Warning:</strong> {{ $value }}
        </div>
    @endsession

    {{-- Success banner --}}
    <div class="mb-8 rounded-lg border border-green-300 bg-green-50 px-6 py-4">
        <p class="text-lg font-semibold text-green-700">&#10003; Production audio uploaded and clean-up complete.</p>
        <p class="mt-1 text-base text-green-600">
            The MP3 has been uploaded to S3 and R2, and the local file has been deleted.
            This episode is now ready for RSS feed generation.
        </p>
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
                    <td class="py-1 text-gray-800 whitespace-nowrap">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- What next --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What would you like to do next?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6">
        <div class="flex flex-col gap-4">
            <a href="{{ route('post_production.generate_rss_feed.step1', $episode) }}"
               class="flex items-center justify-between px-6 py-4 bg-purple-700 text-white rounded-lg font-semibold text-base hover:bg-purple-800 transition-colors">
                <span>Continue to Generate RSS Feed</span>
                <span class="text-xl">&rarr;</span>
            </a>
            <a href="{{ route('post_production.dashboard') }}"
               class="flex items-center justify-between px-6 py-4 border border-purple-300 text-purple-700 rounded-lg font-semibold text-base hover:bg-purple-50 transition-colors">
                <span>Post-Production Dashboard</span>
            </a>
        </div>
    </div>

</x-layouts.app>