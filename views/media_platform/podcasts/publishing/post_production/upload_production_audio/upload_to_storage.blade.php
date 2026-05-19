<x-layouts.app title="Upload to S3 & R2 — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Upload to S3 &amp; R2</h1>
        <a href="{{ route('post_production.upload_production_audio.show', $episode) }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $value }}
        </div>
    @endsession

    {{-- Episode details --}}
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
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Production File</td>
                    <td class="py-1 font-mono text-sm">
                        {{ $expectedFilename }}
                        @if ($fileExists)
                            <span class="ml-2 inline-block rounded bg-green-100 px-2 py-0.5 text-xs text-green-700 font-sans font-semibold">found on server</span>
                        @else
                            <span class="ml-2 inline-block rounded bg-red-100 px-2 py-0.5 text-xs text-red-700 font-sans font-semibold">not found on server</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- What will happen --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What Will Happen</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8 text-sm text-gray-600 space-y-2">
        <p>Clicking <strong>Upload to S3 &amp; R2</strong> will:</p>
        <ul class="mt-2 ml-3 space-y-1 list-disc list-outside pl-5">
            <li>Extract the duration and filesize from the MP3 file.</li>
            <li>Upload <span class="font-mono">{{ $expectedFilename }}</span> to the show's AWS S3 production audio bucket.</li>
            <li>Upload <span class="font-mono">{{ $expectedFilename }}</span> to the show's Cloudflare R2 production audio bucket.</li>
            <li>Save the duration, filesize, and enclosure URL to the episode record.</li>
            <li>Advance the episode status to <strong>Ready to Generate RSS Feed</strong>.</li>
        </ul>
        <p class="mt-3 text-xs text-gray-400">This may take a minute for large files. Do not close the page.</p>
    </div>

    {{-- Action --}}
    @if ($fileExists)

        <form method="POST"
              action="{{ route('post_production.upload_production_audio.upload_to_storage.store', $episode) }}">
            @csrf
            <button type="submit"
                    class="rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Upload to S3 &amp; R2
            </button>
        </form>

    @else

        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">
            The file <span class="font-mono font-medium">{{ $expectedFilename }}</span> was not found on the server.
            Please <a href="{{ route('post_production.upload_production_audio.manual_upload', $episode) }}"
                      class="underline font-semibold">upload it from your computer</a> first.
        </div>

    @endif

</x-layouts.app>