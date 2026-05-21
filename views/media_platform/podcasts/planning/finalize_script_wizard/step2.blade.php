<x-layouts.app title="Finalize Script — Confirm Episode Number">
<div class="max-w-xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="2" />

    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Confirm Episode Number</h1>

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step2.store') }}">
        @csrf

        <div class="mb-6">
            <label for="episode_number" class="block text-base font-semibold text-gray-700 mb-1">Episode Number</label>
            <input type="number" id="episode_number" name="episode_number" min="1"
                   value="{{ old('episode_number', $episode->episode_number) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
            @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step1', $episode) }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                Confirm →
            </button>
        </div>
    </form>

</div>
</x-layouts.app>