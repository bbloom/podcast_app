<x-layouts.app title="Finalize Script — Prepend Intro">
<div class="max-w-3xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="5" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Step 5: Prepend the Intro</h1>
        <p class="text-center text-base text-gray-500 mb-6">
            The intro template has been resolved with your episode details.
            <br>
            Edit if needed, then prepend — or skip if you've already added it manually.
        </p>

        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 mt-4 shadow-sm">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                    alt="{{ $episode->show->title }}"
                    class="w-24 h-24 rounded object-cover border border-purple-200">
            @else
               {{ $episode->show->title ?? '' }} 
            @endif 
            episode #{{ $episode->episode_number }}
            <span class="mt-4">{{ $episode->title }}</span>
        </div>

    @session('info')
        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded text-base">{{ $value }}</div>
    @endsession

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step5.store') }}">
        @csrf

        <div class="mb-6">
            <label for="intro_text" class="block text-base font-semibold text-gray-700 mb-1">Intro Text</label>
            <textarea id="intro_text" name="intro_text" rows="12"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none resize-y">{{ old('intro_text', $resolvedIntro) }}</textarea>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step4') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <div class="flex gap-3">
                <button type="submit" name="_action" value="skip"
                        class="px-5 py-2 border border-gray-400 text-gray-700 rounded font-semibold text-sm hover:bg-gray-50">
                    Skip
                </button>
                <button type="submit" name="_action" value="prepend"
                        class="px-5 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                    Prepend to Script →
                </button>
            </div>
        </div>
    </form>

</div>
</x-layouts.app>