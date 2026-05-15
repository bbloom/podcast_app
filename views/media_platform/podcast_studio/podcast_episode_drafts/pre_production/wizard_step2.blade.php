<x-layouts.app title="Draft Pre-Production — Title">

    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <img src="{{ $draft->show->itunes_image }}" alt="{{ $draft->show->title }}"
                 class="h-[75px] w-[75px] object-cover border border-gray-200 rounded">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Draft Pre-Production</h1>
                @include('media_platform.podcast_studio.podcast_episode_drafts.pre_production._step_dots', ['current' => 2])
                <p class="mt-1 text-sm text-gray-500">Step 2 of 4 — Finalize title, episode number, and date</p>
            </div>
        </div>
    </div>

    {{-- Recent production episodes for reference --}}
    @if ($recentEpisodes->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Recent Production Episodes</h2>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Scheduled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentEpisodes as $ep)
                            <tr>
                                <td class="px-4 py-2 text-gray-500 tabular-nums">{{ $ep->itunes_episode ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-800">{{ $ep->title }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $ep->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form action="{{ route('draft_pre_production.step2.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $draft->title) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This is the production title. It will be used for the website, RSS, and podcast directories.</li>
                <li>The episode number prefix (#N - ) will be added automatically.</li>
                <li>Required.</li>
            </ul>
        </div>

        {{-- Episode Number --}}
        <div>
            <label for="episode_number" class="block text-sm font-semibold text-gray-700 mb-2">Episode Number</label>
            <input type="number" id="episode_number" name="episode_number" value="{{ old('episode_number', $draft->episode_number) }}" min="1"
                   class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('episode_number') border-red-400 @enderror">
            @error('episode_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This is the production episode number. It will be locked in upon episode creation.</li>
                <li>Required.</li>
            </ul>
        </div>

        {{-- Date --}}
        <div>
            <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Scheduled Date</label>
            <input type="date" id="date" name="date" value="{{ old('date', $draft->date?->format('Y-m-d')) }}"
                   class="w-48 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('date') border-red-400 @enderror">
            @error('date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>The scheduled recording or release date.</li>
                <li>Required.</li>
            </ul>
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button type="submit"
                    class="bg-purple-900 hover:bg-purple-700 text-white font-bold py-2 px-8 rounded-lg shadow transition">
                Save &amp; Continue
            </button>
            <a href="{{ route('draft_pre_production.step1') }}"
               class="text-sm text-gray-500 hover:text-purple-700 transition">← Back to Step 1</a>
        </div>
    </form>

</x-layouts.app>