<x-layouts.app title="Submit to Auphonic">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Submit to Auphonic For Processing</h1>
        <a href="{{ route('post_production.dashboard') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Post-Production Dashboard
        </a>
    </div>

    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $value }}
        </div>
    @endsession

    @if ($episodes->isEmpty())

        {{-- Empty state --}}
        <div class="rounded-lg border border-purple-200 bg-purple-50 px-6 py-10 text-center text-gray-500">
            <p class="text-lg font-medium text-purple-700">No episodes are ready for Auphonic.</p>
            <p class="mt-2 text-sm">
                To submit an episode for processing, open it in the Podcast Studio,
                set its status to <span class="font-semibold">Ready for Auphonic</span>, and return here.
            </p>
        </div>

    @else

        {{-- Episodes table --}}
        <div class="border border-purple-500 rounded-lg overflow-hidden">
            <table class="w-full text-sm text-gray-700">
                <thead class="bg-purple-700 text-white text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Show</th>
                        <th class="px-4 py-3 font-semibold">Episode</th>
                        <th class="px-4 py-3 font-semibold">Scheduled Date</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-100">
                    @foreach ($episodes as $episode)
                    <tr class="hover:bg-purple-50 transition-colors">

                        {{-- Show artwork --}}
                        <td class="px-4 py-3">
                            @if ($episode->show->itunes_image)
                                <img src="{{ $episode->show->itunes_image }}"
                                     alt="{{ $episode->show->title }}"
                                     class="w-[75px] h-[75px] rounded-lg object-cover">
                            @else
                                <div class="w-[75px] h-[75px] rounded-lg bg-purple-100 flex items-center justify-center text-xs text-purple-400">
                                    No image
                                </div>
                            @endif
                        </td>

                        {{-- Episode title --}}
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $episode->title }}
                        </td>

                        {{-- Scheduled date --}}
                        <td class="px-4 py-3 text-gray-500">
                            {{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}
                        </td>

                        {{-- Submit link --}}
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('post_production.auphonic_processing.show', $episode) }}"
                               class="inline-block rounded bg-purple-700 px-4 py-1.5 text-xs font-semibold text-white hover:bg-purple-800 transition-colors">
                                Auphonic Processing
                            </a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endif

</x-layouts.app>