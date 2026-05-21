<x-layouts.app title="Prepare For Publishing — Review & Edit">
<div class="max-w-3xl mx-auto px-4 py-10">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="2" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Prepare For Publishing Wizard</h1>
        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 mt-4 shadow-sm">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                    alt="{{ $episode->show->title }}"
                    class="w-24 h-24 rounded object-cover border border-purple-200">
            @else
               {{ $episode->show->title ?? '' }} 
            @endif 
            {{ $episode->title }}
        </div>
    </div>   



    <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Step 2: Review & Edit</h1>
    <p class="text-center text-base text-gray-500 mb-2">
        These are the values that will be used to populate the published episode.
        <br>
        Edit anything that needs correcting before continuing.
    </p>
    <p class="text-center text-base text-gray-500 mb-8">
        If something is fundamentally wrong,
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="text-purple-700 hover:underline">
            go back to the episode
        </a> to fix it first.
    </p>

    {{-- Derived value previews --}}
    <div class="border border-purple-500 rounded-lg overflow-hidden mb-6">
        <div class="bg-purple-50 border-b border-purple-500 px-4 py-2">
            <span class="text-lg font-semibold text-purple-700 uppercase tracking-wider">Derived Values Preview</span>
        </div>
        <table class="w-full text-base px-4">
            <tbody class="divide-y divide-purple-300 bg-white">
                <tr>
                    <td class="px-4 py-2 text-gray-500 w-48">Title</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg">{{ $derived['formatted_title'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Slug</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg">{{ $derived['slug'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Enclosure URL</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg break-all">{{ $derived['enclosure_url'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Raw Audio File</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg">{{ $derived['raw_audio_filename'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">iTunes Link</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg">{{ $derived['itunes_link'] }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-gray-500">Publish On</td>
                    <td class="px-4 py-2 text-gray-800 font-bold text-lg">{{ $derived['publish_on'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- Editable fields --}}
    <br>
    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.publish.step2.store') }}">
        @csrf

        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mb-3">Edit If Needed</div>
        <div class="border border-purple-500 rounded-lg p-5 mb-6 space-y-4">

            {{-- Title --}}
            <div>
                <label for="title" class="block text-base font-semibold text-gray-700 mb-1">
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
                    <label for="episode_number" class="block text-base font-semibold text-gray-700 mb-1">Episode #</label>
                    <input type="number" id="episode_number" name="episode_number" min="1"
                           value="{{ old('episode_number', $episode->episode_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
                    @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="scheduled_date" class="block text-base font-semibold text-gray-700 mb-1">Scheduled Date</label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                           value="{{ old('scheduled_date', $episode->scheduled_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('scheduled_date') border-red-400 @enderror">
                    @error('scheduled_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.publish.step1', $episode) }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                Continue →
            </button>
        </div>

    </form>

</div>
</x-layouts.app>