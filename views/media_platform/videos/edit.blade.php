<x-layouts.app :title="'Edit — ' . $video->title">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('videos.index') }}" class="hover:text-purple-700 transition">← Videos</a>
            <span>›</span>
            <a href="{{ route('videos.show', $video) }}" class="hover:text-purple-700 transition">{{ $video->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Video</h1>
    </div>

    @session('error')
        <div class="mb-5 px-4 py-3 bg-red-50 border border-red-300 text-red-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('videos.update', $video) }}" class="space-y-6 max-w-xl">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">
                Title <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title" value="{{ old('title', $video->title) }}" required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('title') border-red-400 @enderror">
            @error('title')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Slug --}}
        <div>
            <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1">
                Slug <span class="text-red-500">*</span>
            </label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $video->slug) }}" required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 @error('slug') border-red-400 @enderror">
            @error('slug')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">
                Description <span class="text-red-500">*</span>
            </label>
            <textarea id="description" name="description" rows="4" required
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('description') border-red-400 @enderror">{{ old('description', $video->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Scheduled Date --}}
        <div>
            <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-1">
                Scheduled Date
            </label>
            <input type="date" id="scheduled_date" name="scheduled_date"
                   value="{{ old('scheduled_date', $video->scheduled_date?->format('Y-m-d')) }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('scheduled_date') border-red-400 @enderror">
            @error('scheduled_date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div>
            <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">
                Status <span class="text-red-500">*</span>
            </label>
            <select id="status" name="status" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('status') border-red-400 @enderror">
                @foreach (\MediaPlatform\Videos\Enums\VideoStatus::cases() as $statusCase)
                    <option value="{{ $statusCase->value }}" {{ old('status', $video->status->value) === $statusCase->value ? 'selected' : '' }}>
                        {{ str_replace('-', ' ', ucfirst($statusCase->value)) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- YouTube Title --}}
        <div>
            <label for="youtube_title" class="block text-sm font-semibold text-gray-700 mb-1">
                YouTube Title
            </label>
            <input type="text" id="youtube_title" name="youtube_title"
                   value="{{ old('youtube_title', $video->youtube_title) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('youtube_title') border-red-400 @enderror">
            @error('youtube_title')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- YouTube Description --}}
        <div>
            <label for="youtube_description" class="block text-sm font-semibold text-gray-700 mb-1">
                YouTube Description
            </label>
            <textarea id="youtube_description" name="youtube_description" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('youtube_description') border-red-400 @enderror">{{ old('youtube_description', $video->youtube_description) }}</textarea>
            @error('youtube_description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- YouTube Chapters --}}
        <div>
            <label for="youtube_chapters" class="block text-sm font-semibold text-gray-700 mb-1">
                YouTube Chapters
            </label>
            <textarea id="youtube_chapters" name="youtube_chapters" rows="4"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('youtube_chapters') border-red-400 @enderror">{{ old('youtube_chapters', $video->youtube_chapters) }}</textarea>
            @error('youtube_chapters')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- YouTube URL --}}
        <div>
            <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-1">
                YouTube URL
            </label>
            <input type="url" id="youtube_url" name="youtube_url"
                   value="{{ old('youtube_url', $video->youtube_url) }}"
                   placeholder="https://www.youtube.com/watch?v=..."
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 @error('youtube_url') border-red-400 @enderror">
            @error('youtube_url')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 pt-4">
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                Save Changes
            </button>
            <a href="{{ route('videos.show', $video) }}"
               class="text-sm text-gray-500 hover:text-purple-700 transition">Cancel</a>
        </div>

    </form>

</x-layouts.app>