<x-layouts.app title="Finalize Script — Append Outro">
<div class="max-w-3xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="6" />

    <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Append the Outro</h1>
    <p class="text-center text-base text-gray-500 mb-6">
        The outro template has been resolved. Edit if needed, then append —
        or skip if you've already added it manually.
    </p>

    @session('info')
        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded text-base">{{ $value }}</div>
    @endsession

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step6.store') }}">
        @csrf

        <div class="mb-6">
            <label for="outro_text" class="block text-base font-semibold text-gray-700 mb-1">Outro Text</label>
            <textarea id="outro_text" name="outro_text" rows="12"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none resize-y">{{ old('outro_text', $resolvedOutro) }}</textarea>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step5') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <div class="flex gap-3">
                <button type="submit" name="_action" value="skip"
                        class="px-5 py-2 border border-gray-400 text-gray-700 rounded font-semibold text-sm hover:bg-gray-50">
                    Skip
                </button>
                <button type="submit" name="_action" value="append"
                        class="px-5 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                    Append to Script →
                </button>
            </div>
        </div>
    </form>

</div>
</x-layouts.app>