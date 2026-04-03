<x-layouts.app title="Create Podcast Episode — Step 2">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Create Podcast Episode</h1>
        @include('media_platform.podcast_studio.pre_production.create_episode._step_dots', ['current' => 2])
        <p class="mt-3 text-sm text-gray-500">Step 2 of 2 — Episode details</p>
    </div>

    {{-- Selected show reminder --}}
    <div class="flex items-center gap-4 border border-purple-500 rounded-lg px-6 py-4 mb-8 bg-white">
        <img
            src="{{ $show->itunes_image }}"
            alt="{{ $show->title }}"
            class="h-[60px] w-[60px] object-cover border border-gray-200 rounded flex-shrink-0"
        >
        <div>
            <div class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Selected Show</div>
            <div class="text-lg font-bold text-gray-800">{{ $show->title }}</div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('pre_production_create_podcast_episode.step2.store') }}" method="POST">
        @csrf

        <div class="space-y-8">

            {{-- ---------------------------------------------------------------- --}}
            {{-- Title                                                             --}}
            {{-- ---------------------------------------------------------------- --}}
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                    Title
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $defaultTitle) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror"
                    required
                >
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-inside">
                    <li>Spotify episode list on iPhone displays 63 characters.</li>
                    <li>Spotify single episode page on iPhone displays 163 characters.</li>
                    <li>I truncate the final, washed, title to its first 163 characters.</li>
                </ul>
            </div>

            {{-- ---------------------------------------------------------------- --}}
            {{-- iTunes Episode Number                                            --}}
            {{-- ---------------------------------------------------------------- --}}
            <div>
                <label for="itunes_episode" class="block text-sm font-semibold text-gray-700 mb-2">
                    Episode Number
                </label>
                <input
                    type="number"
                    id="itunes_episode"
                    name="itunes_episode"
                    value="{{ old('itunes_episode', $nextNumber) }}"
                    min="1"
                    class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_episode') border-red-400 @enderror"
                    required
                >
                @error('itunes_episode')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                {{-- Recent episodes --}}
                @if ($recentEpisodes->isNotEmpty())
                    <div class="mt-4 border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentEpisodes as $ep)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-gray-500">{{ $ep->id }}</td>
                                        <td class="px-4 py-2 text-gray-800 font-medium">{{ $ep->itunes_episode ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $ep->title }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $ep->status->description ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-3 text-xs text-gray-400">No episodes yet for this show.</p>
                @endif
            </div>

            {{-- ---------------------------------------------------------------- --}}
            {{-- Scheduled Date                                                   --}}
            {{-- ---------------------------------------------------------------- --}}
            <div>
                <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-2">
                    Scheduled Date
                </label>
                <input
                    type="date"
                    id="scheduled_date"
                    name="scheduled_date"
                    value="{{ old('scheduled_date') }}"
                    class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('scheduled_date') border-red-400 @enderror"
                    required
                >
                @error('scheduled_date')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 ml-3 text-xs text-gray-400">What date do you plan on publishing this podcast episode?</p>
            </div>

            {{-- ---------------------------------------------------------------- --}}
            {{-- Website Content                                                  --}}
            {{-- ---------------------------------------------------------------- --}}
            <div>
                <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">
                    Website Content
                </label>
                <textarea
                    id="website_content"
                    name="website_content"
                    rows="8"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_content') border-red-400 @enderror"
                    required
                >{{ old('website_content') }}</textarea>
                @error('website_content')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-inside">
                    <li>One or more sentences describing your episode.</li>
                    <li>You can specify up to 10,000 characters (bytes).</li>
                    <li>You can use rich text formatting and some HTML tags ("p", "ol", "ul", "li", "a").</li>
                    <li>As a practical matter, there is not a lot of real estate on the podcast directories for a long description. A full description, but not a long description.</li>
                </ul>
            </div>

        </div>

        {{-- -------------------------------------------------------------------- --}}
        {{-- Actions                                                               --}}
        {{-- -------------------------------------------------------------------- --}}
        <div class="mt-10 flex items-center gap-4">
            <button
                type="submit"
                class="bg-purple-900 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-white font-bold py-2 px-8 rounded-lg shadow transition"
            >
                Continue
            </button>
            <a
                href="{{ route('pre_production_create_podcast_episode.step1') }}"
                class="text-sm text-gray-500 hover:text-purple-700 transition"
            >
                ← Back to Step 1
            </a>
        </div>

    </form>

</x-layouts.app>