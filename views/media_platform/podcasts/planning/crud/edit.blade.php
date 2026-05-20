<x-layouts.app title="Edit Planning Episode">
<div class="max-w-3xl mx-auto px-4 py-8">

    <p class="text-sm text-gray-500 mb-1">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">
            Planning Episodes
        </a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">
            {{ $episode->formatted_title }}
        </a>
        &rsaquo; Edit
    </p>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Planning Episode</h1>

    <form method="POST" action="{{ route('podcast_episodes_planning.update', $episode) }}">
        @csrf
        @method('PUT')

        {{-- ── Core identity ──────────────────────────────────────────── --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mb-3">Core</div>
        <div class="border border-purple-500 rounded-lg p-5 mb-6 space-y-4">

            {{-- Show — read-only, not changeable after episode creation --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Show</label>
                <p class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{ $episode->show->title ?? '—' }}
                </p>
            </div>

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title"
                       value="{{ old('title', $episode->title) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('title') border-red-400 @enderror">
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Episode number + Scheduled date --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="episode_number" class="block text-sm font-semibold text-gray-700 mb-1">Episode #</label>
                    <input type="number" id="episode_number" name="episode_number" min="1"
                           value="{{ old('episode_number', $episode->episode_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
                    @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-1">Scheduled Date</label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                           value="{{ old('scheduled_date', $episode->scheduled_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('scheduled_date') border-red-400 @enderror">
                    @error('scheduled_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select id="status" name="status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('status') border-red-400 @enderror">
                    @foreach ($manualStatuses as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $episode->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
                <ul class="mt-2 ml-3 space-y-1 text-xs text-gray-500 list-disc list-outside pl-5">
                    <li>Wizard-managed statuses (New Episode Created, Ready To Record) are not shown here — they are set automatically by their respective wizards.</li>
                </ul>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- ── Creative content ───────────────────────────────────────── --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mb-3">Creative Content</div>
        <div class="border border-purple-500 rounded-lg p-5 mb-6 space-y-4">

            {{-- Notes --}}
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('notes') border-red-400 @enderror">{{ old('notes', $episode->notes) }}</textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Theme --}}
            <div>
                <label for="theme" class="block text-sm font-semibold text-gray-700 mb-1">Theme</label>
                <textarea id="theme" name="theme" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('theme') border-red-400 @enderror">{{ old('theme', $episode->theme) }}</textarea>
                @error('theme') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Script --}}
            <div>
                <label for="script" class="block text-sm font-semibold text-gray-700 mb-1">Script</label>
                <textarea id="script" name="script" rows="12"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('script') border-red-400 @enderror">{{ old('script', $episode->script) }}</textarea>
                @error('script') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- ── Website content ────────────────────────────────────────── --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mb-3">Website Content</div>
        <div class="border border-purple-500 rounded-lg p-5 mb-6 space-y-4">

            {{-- Website excerpt --}}
            <div>
                <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-1">Excerpt</label>
                <textarea id="website_excerpt" name="website_excerpt" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('website_excerpt') border-red-400 @enderror">{{ old('website_excerpt', $episode->website_excerpt) }}</textarea>
                @error('website_excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Website content --}}
            <div>
                <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-1">Content</label>
                <textarea id="website_content" name="website_content" rows="8"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none resize @error('website_content') border-red-400 @enderror">{{ old('website_content', $episode->website_content) }}</textarea>
                @error('website_content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded hover:bg-purple-800 font-semibold">
                Save Changes
            </button>
            <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
               class="text-gray-500 hover:underline text-sm">Cancel</a>
        </div>

    </form>
</div>
</x-layouts.app>