<x-layouts.app title="Draft Pre-Production — Script">

    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <img src="{{ $draft->show->itunes_image }}" alt="{{ $draft->show->title }}"
                 class="h-[75px] w-[75px] object-cover border border-gray-200 rounded">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Draft Pre-Production</h1>
                @include('media_platform.podcast_studio.podcast_episode_drafts.pre_production._step_dots', ['current' => 3])
                <p class="mt-1 text-sm text-gray-500">Step 3 of 4 — Finalize draft / script</p>
            </div>
        </div>
    </div>

    {{-- Current draft info --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-6 text-sm text-gray-600">
        <strong>{{ $draft->title }}</strong> — Episode #{{ $draft->episode_number }} — {{ $draft->date?->format('M d, Y') }}
    </div>

    <form action="{{ route('draft_pre_production.step3.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
        @csrf

        <div>
            <label for="draft" class="block text-sm font-semibold text-gray-700 mb-2">Draft / Script</label>
            <textarea id="draft" name="draft" rows="20"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('draft') border-red-400 @enderror">{{ old('draft', $draft->draft) }}</textarea>
            @error('draft') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This is the script you will read from when recording.</li>
                <li>Markdown is supported. Use <code class="text-purple-700"># Heading</code>, <code class="text-purple-700">## Subheading</code>, <code class="text-purple-700">**bold**</code>, and <code class="text-purple-700">* item</code> for bullet points.</li>
                <li>Leave a blank line before and after headings and bullet lists.</li>
                <li>This content will be carried over to the production episode's draft field.</li>
                <li>Required.</li>
            </ul>
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <button type="submit"
                    class="bg-purple-900 hover:bg-purple-700 text-white font-bold py-2 px-8 rounded-lg shadow transition">
                Save &amp; Continue
            </button>
            <a href="{{ route('draft_pre_production.step2') }}"
               class="text-sm text-gray-500 hover:text-purple-700 transition">← Back to Step 2</a>
        </div>
    </form>

</x-layouts.app>