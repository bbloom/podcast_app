<x-layouts.app title="Finalize Script — Confirm">
<div class="max-w-3xl mx-auto px-4 py-10" x-data="{ copied: false }">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="9" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Step 9: Confirm — Ready to Record</h1>
        <p class="text-base text-gray-500 mb-6">
            The script is assembled. Confirming below will set the status to <strong>Ready to Record</strong>.
        </p>

        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 shadow-sm">
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
    </div>

    {{-- Complete assembled script — read-only --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Complete Script</span>
            <button
                @click="navigator.clipboard.writeText(@js($episode->script ?? '')); copied = true; setTimeout(() => copied = false, 2000)"
                class="text-xs font-semibold text-purple-700 hover:text-purple-900 border border-purple-300 rounded px-2 py-1 bg-white">
                <span x-show="!copied">Copy Script</span>
                <span x-show="copied">Copied ✓</span>
            </button>
        </div>
        <div class="px-5 py-4 text-sm font-mono whitespace-pre-wrap text-gray-700 max-h-96 overflow-y-auto bg-white">{{ $episode->script ?? '(no script)' }}</div>
    </div>

    <div class="mb-8 p-4 bg-green-50 border border-green-300 rounded-lg text-base text-green-800">
        <strong>Everything looks good?</strong> Confirm below to set this episode to <strong>Ready to Record</strong>.
        <br><span class="text-sm text-green-700 mt-1 block">You can still make further script tweaks via the episode edit page at any time.</span>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step8') }}"
           class="text-sm text-gray-500 hover:underline">← Back</a>

        <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step9.store') }}">
            @csrf
            <button type="submit"
                    class="px-10 py-3 bg-green-700 text-white rounded-lg font-semibold text-lg hover:bg-green-800">
                Confirm — Ready to Record ✓
            </button>
        </form>
    </div>

</div>
</x-layouts.app>