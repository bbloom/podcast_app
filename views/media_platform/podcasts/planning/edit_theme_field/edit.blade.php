<x-layouts.app title="Edit Theme — {{ $episode->formatted_title }}">
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <p class="text-base text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">Planning Episodes</a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">{{ $episode->formatted_title }}</a>
        &rsaquo; Edit Theme
    </p>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        @if ($episode->show->itunes_image)
            <img src="{{ $episode->show->itunes_image }}"
                 alt="{{ $episode->show->title }}"
                 class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
        @endif
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Theme</h1>
            <p class="text-base text-gray-500 mt-1">{{ $episode->formatted_title }}</p>
        </div>
    </div>

    <div x-data="{
        theme: @js($episode->theme ?? ''),
        saving: false,
        saved: false,
        saveError: '',

        async saveAndContinue() {
            this.saving    = true;
            this.saved     = false;
            this.saveError = '';
            try {
                const res = await fetch('{{ route('podcast_episodes_planning.theme.save', $episode) }}', {
                    method:  'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ theme: this.theme }),
                });
                const data = await res.json();
                if (data.success) {
                    this.saved = true;
                    setTimeout(() => this.saved = false, 3000);
                } else {
                    this.saveError = data.message ?? 'Save failed.';
                }
            } catch (e) {
                this.saveError = 'Save failed. Please try again.';
            } finally {
                this.saving = false;
            }
        }
    }">

        <div class="mb-4">
            <textarea
                x-model="theme"
                rows="16"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-base focus:ring-2 focus:ring-purple-500 focus:outline-none resize-y"
                placeholder="Write the episode theme here..."></textarea>
        </div>

        <div x-show="saved" x-transition class="mb-3 text-base text-green-700 font-medium">✓ Theme saved.</div>
        <div x-show="saveError" x-transition class="mb-3 text-base text-red-600" x-text="saveError"></div>

        <div class="flex items-center gap-4">

            {{-- Save and Continue — Alpine fetch, stays on page --}}
            <button
                @click.prevent="saveAndContinue()"
                :disabled="saving"
                class="px-5 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800 disabled:opacity-50">
                <span x-show="!saving">Save and Continue</span>
                <span x-show="saving">Saving…</span>
            </button>

            {{-- Save and Exit — standard form submit, redirects to show page --}}
            <form method="POST" action="{{ route('podcast_episodes_planning.theme.save_exit', $episode) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="theme" x-model="theme">
                <button type="submit"
                        class="px-5 py-2 border border-gray-400 text-gray-700 rounded font-semibold text-sm hover:bg-gray-50">
                    Save and Exit
                </button>
            </form>

            <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
               class="text-sm text-gray-500 hover:underline ml-2">Cancel</a>
        </div>

    </div>
</div>
</x-layouts.app>