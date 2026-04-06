<x-layouts.app title="Clean Up — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Clean Up Production File</h1>
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
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">File to Delete</td>
                    <td class="py-1 font-mono text-sm">
                        {{ $expectedFilename }}
                        @if ($fileExists)
                            <span class="ml-2 inline-block rounded bg-green-100 px-2 py-0.5 text-xs text-green-700 font-sans font-semibold">found on server</span>
                        @else
                            <span class="ml-2 inline-block rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 font-sans font-semibold">not found — already removed?</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- What will happen --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What Will Happen</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8 text-sm text-gray-600">
        <ul class="ml-3 space-y-1 list-disc list-outside pl-5">
            <li>
                <span class="font-mono">{{ $expectedFilename }}</span> will be permanently deleted from the app server
                (<span class="font-mono">storage/podcasts/</span>).
            </li>
            <li>The file has already been uploaded to S3 and R2 — this step just frees up server disk space.</li>
        </ul>
    </div>

    {{-- Confirm / cancel --}}
    <div class="flex items-center gap-4">

        <form method="POST"
              action="{{ route('post_production.upload_production_audio.cleanup', $episode) }}">
            @csrf
            <button type="submit"
                    class="rounded bg-red-600 px-6 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                Yes, Delete from Server
            </button>
        </form>

        <a href="{{ route('post_production.upload_production_audio.index') }}"
           class="inline-block rounded border border-gray-400 px-6 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Cancel
        </a>

    </div>

</x-layouts.app>