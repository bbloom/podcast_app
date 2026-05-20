<x-layouts.app title="Prepare For Publishing — Review & Edit">
<div class="max-w-3xl mx-auto px-4 py-10">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="2" />

    <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">Review & Edit</h1>
    <p class="text-center text-sm text-gray-500 mb-2">
        These are the values that will be used to populate the published episode.
        Edit anything that needs correcting before continuing.
    </p>
    <p class="text-center text-xs text-gray-400 mb-8">
        If something is fundamentally wrong,
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="text-purple-700 hover:underline">
            go back to the episode
        </a> to fix it first.
    </p>

    {{-- Derived value previews --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-xs font-semibold text-purple-700 uppercase tracking-wider">Derived Values Preview</span>
        </div>
        <table class="w-full text-sm px-4">
            <tbody class="divide-y divide-gray-100">
                <tr>
                    <td class="px-4 py-2 text-gray-500 w-48">Formatted Title</td>
                    <td class="px-4 py-2 text-gray-800 font-medium">{{ $derived['formatted_title'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Slug</td>
                    <td class="px-4 py-2 text-gray-800 font-mono text-xs">{{ $derived['slug'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Enclosure URL</td>
                    <td class="px-4 py-2 text-gray-800 font-mono text-xs break-all">{{ $derived['enclosure_url'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Raw Audio File</td>
                    <td class="px-4 py-2 text-gray-800 font-mono text-xs">{{ $derived['raw_audio_filename'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">iTunes Link</td>
                    <td class="px-4 py-2 text-gray-800 font-mono text-xs break-all">{{ $derived['itunes_link'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Publish On</td>
                    <td class="px-4 py-2 text-gray-800">{{ $derived['publish_on'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Editable fields --}}
    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.publish.step2.store') }}">
        @csrf

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mb-3">Edit If Needed</div>
        <div class="border border-purple-500 rounded-lg p-5 mb-6 space-y-4">

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title"
                       value="{{ old('title', $episode->title) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('title') border-red-400 @enderror">
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Episode number + Scheduled date --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="episode_number" class="block text-sm font-semibold text-gray-700 mb-1">Episode #</label>
                    <input type="number" id="episode_number" name="episode_number" min="1"
                           value="{{ old('episode_number', $episode->episode_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
                    @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-1">Scheduled Date</label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                           value="{{ old('scheduled_date', $episode->scheduled_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('scheduled_date') border-red-400 @enderror">
                    @error('scheduled_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Website excerpt --}}
            <div>
                <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-1">Website Excerpt</label>
                <textarea id="website_excerpt" name="website_excerpt" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('website_excerpt') border-red-400 @enderror">{{ old('website_excerpt', $episode->website_excerpt) }}</textarea>
                @error('website_excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Website content --}}
            <div>
                <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-1">Website Content</label>
                <textarea id="website_content" name="website_content" rows="10"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('website_content') border-red-400 @enderror">{{ old('website_content', $episode->website_content) }}</textarea>
                @error('website_content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.publish.step1', $episode) }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-semibold">
                Looks good — Continue →
            </button>
        </div>
    </form>

</div>
</x-layouts.app>