<x-layouts.app title="Upload Production Audio — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Upload Production Audio</h1>
        <a href="{{ route('post_production.upload_production_audio.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Upload Production Audio
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

            {{-- Show artwork --}}
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif

            {{-- Episode meta --}}
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
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Expected Filename</td>
                    <td class="py-1 text-gray-800 font-mono text-sm">{{ $expectedFilename }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Admin-only: files currently on the server --}}
    @can('admin')
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Files on Server</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8">

        @if (empty($serverFiles))
            <p class="text-sm text-gray-500">No files found in <span class="font-mono">storage/podcasts/</span>.</p>
        @else
            <p class="text-sm text-gray-500 mb-3">
                These are all files currently in <span class="font-mono">storage/podcasts/</span>.
                This list is not scoped per user — use it to confirm your file is present.
            </p>
            <table class="w-full text-sm text-gray-700">
                <thead class="text-left text-gray-500 border-b border-purple-100">
                    <tr>
                        <th class="pb-2 pr-6 font-semibold">Filename</th>
                        <th class="pb-2 pr-6 font-semibold">Size</th>
                        <th class="pb-2 font-semibold">Modified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50">
                    @foreach ($serverFiles as $file)
                    <tr class="{{ $file['name'] === $expectedFilename ? 'bg-green-50' : '' }}">
                        <td class="py-2 pr-6 font-mono text-xs">
                            {{ $file['name'] }}
                            @if ($file['name'] === $expectedFilename)
                                <span class="ml-2 inline-block rounded bg-green-100 px-2 py-0.5 text-xs text-green-700 font-sans font-semibold">expected file</span>
                            @endif
                        </td>
                        <td class="py-2 pr-6 text-gray-500">{{ $file['size'] }}</td>
                        <td class="py-2 text-gray-500">{{ $file['modified'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
    @endcan

    {{-- Decision --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Is the production file on the server?</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8">

        <p class="text-sm text-gray-600 mb-6">
            Is <span class="font-mono font-medium">{{ $expectedFilename }}</span> already on the app server
            (in <span class="font-mono">storage/podcasts/</span>)?
        </p>

        <div class="flex items-center gap-4">

            {{-- Yes — proceed to upload-to-storage --}}
            <a href="{{ route('post_production.upload_production_audio.upload_to_storage', $episode) }}"
               class="inline-block rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Yes — upload to S3 &amp; R2
            </a>

            {{-- No — go to manual upload --}}
            <a href="{{ route('post_production.upload_production_audio.manual_upload', $episode) }}"
               class="inline-block rounded border border-purple-700 px-6 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50 transition-colors">
                No — upload file from my computer
            </a>

        </div>

    </div>

</x-layouts.app>