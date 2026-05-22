<x-layouts.app title="Finalize Script — Confirm Episode Number">
<div class="max-w-xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="2" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Step 2: Confirm Episode Number</h1>

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

    

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step2.store') }}">
        @csrf

        <div class="border-2 border-purple-300 rounded-lg p-6 bg-purple-100 text-base text-gray-700 space-y-4 mb-8">
            <label for="episode_number" class="block text-lg font-bold text-gray-700 mb-1">Episode Number</label>
            <input type="number" id="episode_number" name="episode_number" min="1"
                   value="{{ old('episode_number', $episode->episode_number) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
            @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <br>
        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step1', $episode) }}"
               class="text-sm text-gray-700 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-green-700 text-white rounded font-semibold text-sm hover:bg-green-800">
                Confirm →
            </button>
        </div>
    </form>

</div>
</x-layouts.app>