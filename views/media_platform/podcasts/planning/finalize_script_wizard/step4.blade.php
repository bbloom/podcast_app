<x-layouts.app title="Finalize Script — AI Proofing">
<div class="max-w-3xl mx-auto px-4 py-10" x-data="{ copied: false }">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="4" />

    <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">AI Proofing</h1>
    <p class="text-center text-sm text-gray-500 mb-6">Review the script with AI tools before finalizing.</p>

    {{-- Script read-only view with copy button --}}
    <div class="border border-purple-300 rounded-lg overflow-hidden mb-6">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Script</span>
            <button
                @click="navigator.clipboard.writeText(@js($episode->script ?? '')).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                class="text-xs px-3 py-1 border border-purple-400 text-purple-700 rounded hover:bg-purple-100">
                <span x-show="!copied">Copy Script</span>
                <span x-show="copied">Copied!</span>
            </button>
        </div>
        <div class="px-4 py-4 max-h-64 overflow-y-auto">
            @if ($episode->script)
                <pre class="text-xs font-mono whitespace-pre-wrap text-gray-800 leading-relaxed">{{ $episode->script }}</pre>
            @else
                <p class="text-gray-400 text-sm">No script yet.</p>
            @endif
        </div>
    </div>

    {{-- AI tool links --}}
    <div class="border border-gray-200 rounded-lg p-5 mb-6">
        <p class="text-sm font-semibold text-gray-700 mb-3">AI Tools</p>
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="/adhocprompt" class="px-3 py-1.5 border border-purple-400 text-purple-700 rounded hover:bg-purple-50">Ad Hoc Prompt (internal)</a>
            <a href="https://gemini.google.com" target="_blank" rel="noopener" class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">Gemini ↗</a>
            <a href="https://chatgpt.com" target="_blank" rel="noopener" class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">ChatGPT ↗</a>
        </div>
    </div>

    {{-- Suggested prompts --}}
    <div class="border border-gray-200 rounded-lg p-5 mb-8 space-y-5">
        <p class="text-sm font-semibold text-gray-700">Suggested Prompts <span class="text-gray-400 font-normal text-xs">(copy and paste into your AI tool)</span></p>

        @foreach ([
            ['Spelling & Grammar',              'Please check the following podcast script for spelling and grammar errors. List each error with the suggested correction.'],
            ['Conversational Flow',             'Please review the following podcast script for conversational flow. This script will be read aloud. Identify any sentences or passages that sound unnatural when spoken, are too formal, or would be awkward to say aloud. Suggest more natural alternatives.'],
            ['Sentence Length & Readability',   'Please review the following podcast script for sentences that are too long or complex to read aloud comfortably. Suggest shorter, more natural alternatives where needed.'],
            ['Full Polish',                     'Please review the following podcast script for overall quality. This script will be read aloud. Check for: spelling and grammar errors, conversational flow and natural spoken language, sentence length and readability when read aloud, clarity of ideas, and natural pacing. Provide specific, actionable suggestions for improvement.'],
        ] as [$label, $prompt])
        <div x-data="{ copied: false }">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $label }}</span>
                <button @click="navigator.clipboard.writeText('{{ addslashes($prompt) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                        class="text-xs text-purple-700 hover:underline">
                    <span x-show="!copied">Copy</span><span x-show="copied">Copied!</span>
                </button>
            </div>
            <p class="text-xs text-gray-600 italic">{{ $prompt }}</p>
        </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step3') }}"
           class="text-sm text-gray-500 hover:underline">← Back</a>
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step5') }}"
           class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-semibold">
            Continue →
        </a>
    </div>

</div>
</x-layouts.app>