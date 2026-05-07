<x-layouts.app title="Create Video — Step 1">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Create Video</h1>
    </div>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    <form action="{{ route('videos.create.step1.store') }}" method="POST" class="space-y-8 max-w-xl">
        @csrf

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                Title <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                id="title"
                name="title"
                value="{{ old('title', session('wizard.create_video.title')) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror"
                required
                autofocus
            >
            @error('title')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-400">The working title of the video. This can be changed later.</p>
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                Description <span class="text-red-500">*</span>
            </label>
            <textarea
                id="description"
                name="description"
                rows="4"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('description') border-red-400 @enderror"
                required
            >{{ old('description', session('wizard.create_video.description')) }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-400">A brief summary of what this video covers. Used as the basis for the YouTube description.</p>
        </div>

        {{-- Scheduled Date --}}
        <div>
            <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-2">
                Scheduled Date <span class="text-red-500">*</span>
            </label>
            <input
                type="date"
                id="scheduled_date"
                name="scheduled_date"
                value="{{ old('scheduled_date', session('wizard.create_video.scheduled_date')) }}"
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('scheduled_date') border-red-400 @enderror"
            >
            @error('scheduled_date')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-400">The date you plan to publish this video to YouTube.</p>
        </div>

        {{-- Actions --}}
        <div class="mt-10 flex items-center gap-4">
            <button
                type="submit"
                class="bg-purple-900 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-white font-bold py-2 px-8 rounded-lg shadow transition"
            >
                Continue
            </button>
            <a
                href="{{ route('videos.index') }}"
                class="text-sm text-gray-500 hover:text-purple-700 transition"
            >
                Cancel
            </a>
        </div>

    </form>

</x-layouts.app>