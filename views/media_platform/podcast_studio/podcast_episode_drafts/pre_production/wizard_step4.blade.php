<x-layouts.app title="Draft Pre-Production — Website Content">

    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <img src="{{ $draft->show->itunes_image }}" alt="{{ $draft->show->title }}"
                 class="h-[75px] w-[75px] object-cover border border-gray-200 rounded">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Draft Pre-Production</h1>
                @include('media_platform.podcast_studio.podcast_episode_drafts.pre_production._step_dots', ['current' => 4])
                <p class="mt-1 text-sm text-gray-500">Step 4 of 4 — Finalize website content</p>
            </div>
        </div>
    </div>

    {{-- Current draft info --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-6 text-sm text-gray-600">
        <strong>{{ $draft->title }}</strong> — Episode #{{ $draft->episode_number }} — {{ $draft->date?->format('M d, Y') }}
    </div>

    <form action="{{ route('draft_pre_production.step4.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        {{-- Website Content --}}
        <div>
            <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">Website Content</label>
            <textarea id="website_content" name="website_content" rows="10"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_content') border-red-400 @enderror">{{ old('website_content', $draft->website_content) }}</textarea>
            @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This is the episode description for the website. It cascades into multiple RSS fields upon episode creation.</li>
                <li>HTML tags allowed: &lt;p&gt;, &lt;ol&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;.</li>
                <li>This is the last step. Once saved, the draft will be marked as pre-production complete.</li>
                <li>Required.</li>
            </ul>
        </div>

        {{-- Website Excerpt --}}
        <div>
            <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">Website Excerpt</label>
            <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt', $draft->website_excerpt) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror"
                   placeholder="Short summary for website listings (max 255 chars)">
            @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>If left blank, it will be derived from website content at episode creation.</li>
                <li>Optional.</li>
            </ul>
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button type="submit"
                    class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-8 rounded-lg shadow transition">
                Complete Pre-Production
            </button>
            <a href="{{ route('draft_pre_production.step3') }}"
               class="text-sm text-gray-500 hover:text-purple-700 transition">← Back to Step 3</a>
        </div>
    </form>

</x-layouts.app>