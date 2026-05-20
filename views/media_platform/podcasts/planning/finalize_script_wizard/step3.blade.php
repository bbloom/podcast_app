<x-layouts.app title="Finalize Script — Confirm Title">
<div class="max-w-xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="3" />

    <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">Confirm Title</h1>
    <p class="text-center text-sm text-gray-500 mb-6">
        Will be displayed as: <strong>#{{ $episode->episode_number }} — {{ $episode->title }}</strong>
    </p>

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step3.store') }}">
        @csrf

        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
            <input type="text" id="title" name="title"
                   value="{{ old('title', $episode->title) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('title') border-red-400 @enderror">
            <ul class="mt-2 ml-3 space-y-1 text-xs text-gray-500 list-disc list-outside pl-5">
                <li>The episode number prefix (#N —) is added automatically on publishing.</li>
            </ul>
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step2') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-semibold">
                Confirm →
            </button>
        </div>
    </form>

</div>
</x-layouts.app>