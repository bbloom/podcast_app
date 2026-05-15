<x-layouts.app title="Edit Podcast Episode Draft">

    {{-- Breadcrumb --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episode_drafts.index') }}" class="hover:text-purple-700 transition">← Podcast Episode Drafts</a>
            <span>›</span>
            <a href="{{ route('podcast_episode_drafts.show', $draft) }}" class="hover:text-purple-700 transition">{{ $draft->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Podcast Episode Draft</h1>
    </div>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    <form action="{{ route('podcast_episode_drafts.update', $draft) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ================================================================ --}}
        {{-- GENERAL                                                           --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">General</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            {{-- Podcast Show --}}
            <div class="mb-6">
                <label for="podcast_show_id" class="block text-sm font-semibold text-gray-700 mb-2">Podcast Show</label>
                <select name="podcast_show_id" id="podcast_show_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('podcast_show_id') border-red-400 @enderror">
                    @foreach ($shows as $show)
                        <option value="{{ $show->id }}" @selected(old('podcast_show_id', $draft->podcast_show_id) == $show->id)>
                            {{ $show->title }}
                        </option>
                    @endforeach
                </select>
                @error('podcast_show_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The podcast show this draft belongs to.</li>
                </ul>
            </div>

            {{-- Title --}}
            <div class="mb-6">
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $draft->title) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
                @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Working title for this draft. Does not need to be final.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Episode Number --}}
            <div class="mb-6">
                <label for="episode_number" class="block text-sm font-semibold text-gray-700 mb-2">Episode Number</label>
                <input type="number" id="episode_number" name="episode_number" value="{{ old('episode_number', $draft->episode_number) }}" min="1"
                       class="w-32 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('episode_number') border-red-400 @enderror">
                @error('episode_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Tentative episode number. May change before graduation.</li>
                    <li>Optional.</li>
                </ul>
            </div>

            {{-- Tentative Date --}}
            <div class="mb-0">
                <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Tentative Date</label>
                <input type="date" id="date" name="date" value="{{ old('date', $draft->date?->format('Y-m-d')) }}"
                       class="w-48 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('date') border-red-400 @enderror">
                @error('date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Tentative recording or release date. No commitment implied.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- DRAFT                                                             --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Draft</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="draft" class="block text-sm font-semibold text-gray-700 mb-2">Draft / Script</label>
                <textarea id="draft" name="draft" rows="12"
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('draft') border-red-400 @enderror">{{ old('draft', $draft->draft) }}</textarea>
                @error('draft') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The written script or draft text. Will be carried over to the episode's draft field upon graduation.</li>
                    <li>Markdown is supported. Use headings, bold, lists, and links to structure your draft for better readability and downstream use (transcripts, AI summaries).</li>
                    <li>Markdown tips: use <code class="text-purple-700"># Heading</code> for headings, <code class="text-purple-700">## Subheading</code> for subheadings, <code class="text-purple-700">**bold**</code> for bold, and <code class="text-purple-700">* item</code> for bullet points.</li>
                    <li>Important: leave a blank line before and after headings and bullet lists for them to render correctly.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- WEBSITE                                                           --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Website</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            {{-- Website Content --}}
            <div class="mb-6">
                <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">Website Content</label>
                <textarea id="website_content" name="website_content" rows="8"
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_content') border-red-400 @enderror">{{ old('website_content', $draft->website_content) }}</textarea>
                @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Episode description for the website. This is the field that cascades into multiple RSS fields upon episode creation.</li>
                    <li>Refine this here during drafting so you're confident when crossing the one-way door.</li>
                    <li>HTML tags allowed: &lt;p&gt;, &lt;ol&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;.</li>
                    <li>Required at episode creation. Optional during drafting.</li>
                </ul>
            </div>

            {{-- Website Excerpt --}}
            <div class="mb-0">
                <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">Website Excerpt</label>
                <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt', $draft->website_excerpt) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror"
                       placeholder="Short summary for website listings (max 255 chars)">
                @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Short excerpt for website listings. If left blank, it will be derived from website content at episode creation.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- GUEST                                                             --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Guest</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="guest_notes" class="block text-sm font-semibold text-gray-700 mb-2">Guest Notes</label>
                <input type="text" id="guest_notes" name="guest_notes" value="{{ old('guest_notes', $draft->guest_notes) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('guest_notes') border-red-400 @enderror"
                       placeholder="Prospective guest names or notes">
                @error('guest_notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Optional free-form notes about prospective guests not yet in the system.</li>
                    <li>For confirmed guests, use the attach/detach feature on the draft's show page.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- BASECAMP                                                          --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Basecamp</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="basecamp_url" class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
                <input type="url" id="basecamp_url" name="basecamp_url" value="{{ old('basecamp_url', $draft->basecamp_url) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('basecamp_url') border-red-400 @enderror"
                       placeholder="https://3.basecamp.com/...">
                @error('basecamp_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>URL to the Basecamp project for this episode, if one exists.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- COMMENTS                                                          --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Comments</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Comments</label>
                <input type="text" id="comments" name="comments" value="{{ old('comments', $draft->comments) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('comments') border-red-400 @enderror"
                       placeholder="Status notes or reminders">
                @error('comments') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Status notes, reminders, or general comments about this draft.</li>
                    <li>Optional.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- ACTIONS                                                           --}}
        {{-- ================================================================ --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('podcast_episode_drafts.delete.confirm', $draft) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this draft
            </a>
            <div class="flex gap-3">
                <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                    Save Changes
                </button>
            </div>
        </div>

    </form>

</x-layouts.app>