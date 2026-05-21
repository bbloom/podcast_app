<x-layouts.app title="Finalize Script — Final Proof">
<div class="max-w-3xl mx-auto px-4 py-10" x-data="{ copied: false }">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="7" />

    <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Final Proof</h1>
    <p class="text-center text-base text-gray-500 mb-6">
        Read through the complete assembled script one final time.
        When you confirm, the status will be set to <strong>Ready To Record</strong>.
    </p>

    <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Complete Script</span>
            <button
                @click="navigator.clipboard.writeText(@js($episode->script ?? '')).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                class="text-xs px-3 py-1 border border-purple-400 text-purple-700 rounded hover:bg-purple-100">
                <span x-show="!copied">Copy Script</span>
                <span x-show="copied">Copied!</span>
            </button>
        </div>
        <div class="px-4 py-4 max-h-[60vh] overflow-y-auto">
            @if ($episode->script)
                <pre class="text-xs font-mono whitespace-pre-wrap text-gray-800 leading-relaxed">{{ $episode->script }}</pre>
            @else
                <p class="text-base text-gray-400">No script.</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step7.store') }}">
        @csrf
        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.finalize.step6') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-3 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700">
                Script is ready — lock it for recording ✓
            </button>
        </div>
    </form>

</div>
</x-layouts.app>