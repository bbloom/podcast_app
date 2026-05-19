<x-layouts.app title="Regenerate RSS Feed">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Regenerate RSS Feed</h1>
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

    @session('success')
        <div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $value }}
        </div>
    @endsession

    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Select a Show</div>
    <div class="border border-purple-500 rounded-lg px-4 py-2 mb-8">

        @if ($shows->isEmpty())

            <p class="text-sm text-gray-500 py-4 text-center">No podcast shows found.</p>

        @else

            <table class="w-full text-sm text-gray-700">
                <tbody class="divide-y divide-purple-100">
                    @foreach ($shows as $show)
                    <tr class="hover:bg-purple-50 transition-colors">

                        <td class="px-4 py-3">
                            @if ($show->itunes_image)
                                <img src="{{ $show->itunes_image }}"
                                     alt="{{ $show->title }}"
                                     class="w-[60px] h-[60px] rounded-lg object-cover">
                            @else
                                <div class="w-[60px] h-[60px] rounded-lg bg-purple-100 flex items-center justify-center text-xs text-purple-400">
                                    No image
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $show->title }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('post_production.regenerate_rss_feed.stage', $show) }}"
                               class="inline-block rounded bg-purple-700 px-4 py-1.5 text-xs font-semibold text-white hover:bg-purple-800 transition-colors">
                                Regenerate
                            </a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

    </div>

</x-layouts.app>