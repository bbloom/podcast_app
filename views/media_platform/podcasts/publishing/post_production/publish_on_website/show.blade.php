<x-layouts.app title="Publish on Website — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Publish on Website</h1>
        <a href="{{ route('post_production.publish_on_website.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Publish on Website
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $value }}
        </div>
    @endsession

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
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-2 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Publish On</td>
                    <td class="py-2 text-gray-800">{{ $episode->website_publish_on?->format('M j, Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-2 text-gray-500 whitespace-nowrap align-top">Website Link</td>
                    <td class="py-2">
                        @if ($episode->itunes_link)
                            <a href="{{ $episode->itunes_link }}"
                               target="_blank"
                               class="text-purple-700 hover:underline text-sm">
                                {{ $episode->itunes_link }} &nearr;
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Review --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Review</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8 text-sm text-gray-600">
        <p>Before publishing, review all episode fields to ensure everything is correct.</p>
        <p class="mt-3">
            <a href="{{ route('podcast_episodes.show', $episode) }}"
               target="_blank"
               class="inline-block rounded bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-200 transition-colors">
                Open Episode Page &rarr;
            </a>
            <span class="ml-3 text-xs text-gray-400">Opens in a new tab. Return here when you are satisfied.</span>
        </p>
    </div>

    {{-- What will happen --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">What Will Happen</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8 text-sm text-gray-600">
        <ul class="ml-3 space-y-1 list-disc list-outside pl-5">
            <li>The episode will be set to <strong>visible on the website</strong>.</li>
            <li>The episode status will advance to <strong>Published</strong>.</li>
            <li><strong>website_publish_on</strong> will not be changed — it was set when the episode was created.</li>
        </ul>
    </div>

    {{-- Confirm --}}
    <div class="flex items-center gap-4">
        <form method="POST"
              action="{{ route('post_production.publish_on_website.publish', $episode) }}">
            @csrf
            <button type="submit"
                    class="rounded bg-purple-700 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors">
                Publish on Website
            </button>
        </form>

        <a href="{{ route('post_production.publish_on_website.index') }}"
           class="inline-block rounded border border-gray-400 px-6 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            Cancel
        </a>
    </div>

</x-layouts.app>