<x-layouts.app title="Finalize Script — AI Proofing">
<div class="max-w-7xl mx-auto px-4 py-10"
     x-data="{
         script:       @js($episode->script ?? ''),
         scratch:      @js($episode->script_scratch ?? ''),
         savingScript: false,
         savingScratch: false,
         scriptMsg:    '',
         scratchMsg:   '',
         copiedScript: false,
         copiedScratch: false,
         async saveScript() {
             this.savingScript = true;
             this.scriptMsg = '';
             try {
                 const res = await fetch('{{ route('podcast_episodes_planning.script.save', $episode) }}', {
                     method: 'PATCH',
                     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                     body: JSON.stringify({ script: this.script })
                 });
                 const data = await res.json();
                 this.scriptMsg = data.success ? 'Saved.' : 'Error — try again.';
             } catch (e) {
                 this.scriptMsg = 'Error — try again.';
             }
             this.savingScript = false;
         },
         async saveScratch() {
             this.savingScratch = true;
             this.scratchMsg = '';
             try {
                 const res = await fetch('{{ route('podcast_episodes_planning.wizard.finalize.step4.save_scratch') }}', {
                     method: 'PATCH',
                     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                     body: JSON.stringify({ script_scratch: this.scratch })
                 });
                 const data = await res.json();
                 this.scratchMsg = data.success ? 'Saved.' : 'Error — try again.';
             } catch (e) {
                 this.scratchMsg = 'Error — try again.';
             }
             this.savingScratch = false;
         }
     }">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="4" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Step 4: Proof Script with AI</h1>
        <p class="text-base text-gray-500 mb-6">
            Copy your script into an AI tool, apply suggestions, then paste the result into the scratch pad for comparison.
            <br>When you are happy, update the main script and save before continuing.
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

    {{-- ── Suggested prompts ─────────────────────────────────────────────────── --}}
    <div class="border border-purple-200 rounded-lg bg-purple-50 px-5 py-4 mb-6 text-base text-gray-700">
        <p class="font-semibold text-purple-700 mb-2">Suggested AI prompts</p>
        <ul class="space-y-2 list-disc list-inside text-sm text-gray-600">
            <li><strong>Quick grammar pass:</strong> "Please correct any spelling and grammar errors in the following podcast script. Preserve the original wording and structure as much as possible."</li>
            <li><strong>Tighten sentences:</strong> "Review this podcast script for any overly long or complex sentences. Suggest shorter, more natural alternatives where needed."</li>
            <li><strong>Full polish:</strong> "Review the following podcast script for overall quality. This script will be read aloud. Check for: spelling and grammar errors, conversational flow, sentence length and readability, clarity of ideas, and natural pacing. Provide specific, actionable suggestions."</li>
        </ul>
    </div>

    {{-- ── Two-column editing area ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        {{-- Main Script --}}
        <div class="flex flex-col">
            <div class="bg-purple-50 border border-purple-300 rounded-t-lg px-4 py-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-purple-700 uppercase tracking-wider">Main Script</span>
                <div class="flex items-center gap-3">
                    <span x-show="scriptMsg" x-text="scriptMsg"
                          :class="scriptMsg === 'Saved.' ? 'text-green-600' : 'text-red-600'"
                          class="text-xs font-medium"></span>
                    <button @click="navigator.clipboard.writeText(script); copiedScript = true; setTimeout(() => copiedScript = false, 2000)"
                            class="text-xs text-purple-700 hover:text-purple-900 font-semibold border border-purple-300 rounded px-2 py-1 bg-white">
                        <span x-show="!copiedScript">Copy</span>
                        <span x-show="copiedScript">Copied ✓</span>
                    </button>
                </div>
            </div>
            <textarea x-model="script" rows="24"
                      class="w-full border-x border-b border-purple-300 rounded-b-lg px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none resize-y bg-white"></textarea>
            <div class="mt-2 flex justify-end">
                <button @click="saveScript()"
                        :disabled="savingScript"
                        class="px-5 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800 disabled:opacity-50">
                    <span x-show="!savingScript">Save Script</span>
                    <span x-show="savingScript">Saving…</span>
                </button>
            </div>
        </div>

        {{-- AI Scratch Pad --}}
        <div class="flex flex-col">
            <div class="bg-amber-50 border border-amber-300 rounded-t-lg px-4 py-2 flex items-center justify-between">
                <div>
                    <span class="text-sm font-semibold text-amber-700 uppercase tracking-wider">AI Scratch Pad</span>
                    <span class="ml-2 text-xs text-amber-600">— paste AI output here for comparison</span>
                </div>
                <div class="flex items-center gap-3">
                    <span x-show="scratchMsg" x-text="scratchMsg"
                          :class="scratchMsg === 'Saved.' ? 'text-green-600' : 'text-red-600'"
                          class="text-xs font-medium"></span>
                    <button @click="navigator.clipboard.writeText(scratch); copiedScratch = true; setTimeout(() => copiedScratch = false, 2000)"
                            class="text-xs text-amber-700 hover:text-amber-900 font-semibold border border-amber-300 rounded px-2 py-1 bg-white">
                        <span x-show="!copiedScratch">Copy</span>
                        <span x-show="copiedScratch">Copied ✓</span>
                    </button>
                </div>
            </div>
            <textarea x-model="scratch" rows="24"
                      class="w-full border-x border-b border-amber-300 rounded-b-lg px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-amber-400 focus:outline-none resize-y bg-amber-50/30"></textarea>
            <div class="mt-2 flex justify-end">
                <button @click="saveScratch()"
                        :disabled="savingScratch"
                        class="px-5 py-2 bg-amber-600 text-white rounded font-semibold text-sm hover:bg-amber-700 disabled:opacity-50">
                    <span x-show="!savingScratch">Save Scratch</span>
                    <span x-show="savingScratch">Saving…</span>
                </button>
            </div>
        </div>

    </div>

    {{-- ── Navigation ────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mt-4">
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step3') }}"
           class="text-sm text-gray-500 hover:underline">← Back</a>
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step5') }}"
           class="inline-block px-8 py-3 bg-green-700 text-white rounded-lg font-semibold hover:bg-green-800 text-sm">
            Continue to Step 5 →
        </a>
    </div>

</div>
</x-layouts.app>