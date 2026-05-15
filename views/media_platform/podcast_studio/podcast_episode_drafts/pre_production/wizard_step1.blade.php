<x-layouts.app title="Draft Pre-Production">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Draft Pre-Production</h1>
        @include('media_platform.podcast_studio.podcast_episode_drafts.pre_production._step_dots', ['current' => 1])
        <p class="mt-3 text-sm text-gray-500">Step 1 of 4 — Select a draft to take through pre-production</p>
    </div>

    @if (session('error'))
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @foreach ($shows as $show)
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <img
                    src="{{ $show->itunes_image }}"
                    alt="{{ $show->title }}"
                    class="h-[75px] w-[75px] object-cover border border-gray-200 rounded"
                >
                <h2 class="text-xl font-bold text-gray-800">{{ $show->title }}</h2>
            </div>

            @if ($show->drafts->isEmpty())
                <div class="border border-gray-200 rounded-lg px-6 py-6 text-center text-sm text-gray-400 mb-4">
                    No drafts in progress for this show.
                </div>
            @else
                <div class="border border-gray-200 rounded-lg overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ep#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-400">
                            @foreach ($show->drafts as $draft)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-600 tabular-nums">{{ $draft->episode_number ?? '—' }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-800">{{ $draft->title }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $draft->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('draft_pre_production.step1.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="podcast_episode_draft_id" value="{{ $draft->id }}">
                                            <button type="submit"
                                                    class="bg-purple-900 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                                                Start Pre-Production
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach

</x-layouts.app>