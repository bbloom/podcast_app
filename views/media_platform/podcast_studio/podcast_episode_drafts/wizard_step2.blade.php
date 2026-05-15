<x-layouts.app title="Create Episode Draft">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <img
                src="{{ $show->itunes_image }}"
                alt="{{ $show->title }}"
                class="h-[75px] w-[75px] object-cover border border-gray-200 rounded"
            >
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Create Podcast Episode Draft</h1>
                @include('media_platform.podcast_studio.podcast_episode_drafts._step_dots', ['current' => 2])
                <p class="mt-1 text-sm text-gray-500">Step 2 of 2 — {{ $show->title }}</p>
            </div>
        </div>
    </div>

    {{-- ── Recent Production Episodes (convenience reference) ────────────── --}}
    @if ($recentEpisodes->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Recent Production Episodes</h2>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Scheduled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentEpisodes as $ep)
                            <tr>
                                <td class="px-4 py-2 text-gray-500 tabular-nums">{{ $ep->itunes_episode ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-800">{{ $ep->title }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $ep->status?->label() ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $ep->scheduled_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Recent Draft Episodes ────────────────────────────────────────── --}}
    @if ($existingDrafts->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Recent Draft Episodes</h2>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($existingDrafts as $d)
                            <tr>
                                <td class="px-4 py-2 text-gray-500 tabular-nums">{{ $d->episode_number ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('podcast_episode_drafts.show', $d) }}"
                                       class="text-purple-700 hover:underline">{{ $d->title }}</a>
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $d->date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Draft Form ───────────────────────────────────────────────────── --}}
    <form action="{{ route('podcast_episode_drafts.create.step2.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        <input type="hidden" name="podcast_show_id" value="{{ $show->id }}">

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror"
                   placeholder="Working title for this episode">
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Episode Number --}}
        <div>
            <label for="episode_number" class="block text-sm font-semibold text-gray-700 mb-2">Episode Number</label>
            <input type="number" id="episode_number" name="episode_number" value="{{ old('episode_number', $nextNumber) }}" min="1"
                   class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('episode_number') border-red-400 @enderror">
            @error('episode_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">Next available: {{ $nextNumber }}</p>
        </div>

        {{-- Tentative Date --}}
        <div>
            <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Tentative Date</label>
            <input type="date" id="date" name="date" value="{{ old('date') }}"
                   class="w-48 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('date') border-red-400 @enderror">
            @error('date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Draft --}}
        <div>
            <label for="draft" class="block text-sm font-semibold text-gray-700 mb-2">Draft</label>
            <textarea id="draft" name="draft" rows="10"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('draft') border-red-400 @enderror"
                      placeholder="Write your episode script or notes here... (Markdown supported)">{{ old('draft') }}</textarea>
            @error('draft') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>Markdown is supported. Use headings, bold, lists, and links to structure your draft.</li>
                <li>Markdown tips: use <code class="text-purple-700"># Heading</code> for headings, <code class="text-purple-700">## Subheading</code> for subheadings, <code class="text-purple-700">**bold**</code> for bold, and <code class="text-purple-700">* item</code> for bullet points.</li>
                <li>Important: leave a blank line before and after headings and bullet lists for them to render correctly.</li>
                <li>Optional.</li>
            </ul>
        </div>

        {{-- Guest Notes --}}
        <div>
            <label for="guest_notes" class="block text-sm font-semibold text-gray-700 mb-2">Guest Notes</label>
            <input type="text" id="guest_notes" name="guest_notes" value="{{ old('guest_notes') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('guest_notes') border-red-400 @enderror"
                   placeholder="Prospective guest names or notes">
            @error('guest_notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Comments --}}
        <div>
            <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Comments</label>
            <input type="text" id="comments" name="comments" value="{{ old('comments') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('comments') border-red-400 @enderror"
                   placeholder="Status notes or reminders">
            @error('comments') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Basecamp URL --}}
        <div>
            <label for="basecamp_url" class="block text-sm font-semibold text-gray-700 mb-2">Basecamp URL</label>
            <input type="url" id="basecamp_url" name="basecamp_url" value="{{ old('basecamp_url') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('basecamp_url') border-red-400 @enderror"
                   placeholder="https://3.basecamp.com/...">
            @error('basecamp_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website Content --}}
        <div>
            <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">Website Content</label>
            <textarea id="website_content" name="website_content" rows="6"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_content') border-red-400 @enderror"
                      placeholder="Episode description for the website (optional at this stage)">{{ old('website_content') }}</textarea>
            @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This field cascades into multiple RSS fields upon episode creation. You can write it now or refine it later via edit.</li>
                <li>Optional during drafting.</li>
            </ul>
        </div>

        {{-- Website Excerpt --}}
        <div>
            <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">Website Excerpt</label>
            <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt') }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror"
                   placeholder="Short summary for website listings (max 255 chars)">
            @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button
                type="submit"
                class="bg-purple-900 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-white font-bold py-2 px-8 rounded-lg shadow transition"
            >
                Create Draft
            </button>
            <a
                href="{{ route('podcast_episode_drafts.create.step1') }}"
                class="text-sm text-gray-500 hover:text-purple-700 transition"
            >
                ← Back to Step 1
            </a>
        </div>

    </form>

</x-layouts.app>