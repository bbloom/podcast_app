<x-layouts.app :title="$video->title">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('videos.index') }}" class="hover:text-purple-700 transition">← Videos</a>
            <span>›</span>
            <span class="text-gray-700">{{ $video->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $video->title }}</h1>
            <a href="{{ route('videos.edit', $video) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">Edit</a>
        </div>
    </div>

    @session('success')
        <div class="mb-5 px-4 py-3 bg-green-50 border border-green-300 text-green-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="mb-5 px-4 py-3 bg-red-50 border border-red-300 text-red-800 rounded text-sm">
            {{ $value }}
        </div>
    @endsession

    {{-- Details card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <dl class="divide-y divide-gray-200 text-sm">

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">ID</dt>
                <dd class="text-gray-600">{{ $video->id }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Slug</dt>
                <dd>
                    <span class="font-mono text-xs bg-white border border-gray-200 rounded px-2 py-0.5 text-gray-600">
                        {{ $video->slug }}
                    </span>
                </dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Description</dt>
                <dd class="text-gray-600 whitespace-pre-line">{{ $video->description }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Scheduled Date</dt>
                <dd class="text-gray-600">{{ $video->scheduled_date?->format('F d, Y') ?? '—' }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">Status</dt>
                <dd>
                    @if ($video->status === \MediaPlatform\Videos\Enums\VideoStatus::published_to_youtube)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published to YouTube</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Not Published</span>
                    @endif
                </dd>
            </div>

        </dl>
    </div>

    {{-- YouTube card --}}
    <div class="border border-purple-500 rounded-lg p-4 mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-4">YouTube</h2>
        <dl class="divide-y divide-gray-200 text-sm">

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">YouTube Title</dt>
                <dd class="text-gray-600">{{ $video->youtube_title ?? '—' }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">YouTube Description</dt>
                <dd class="text-gray-600 whitespace-pre-line">{{ $video->youtube_description ?? '—' }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">YouTube Chapters</dt>
                <dd class="text-gray-600 whitespace-pre-line">{{ $video->youtube_chapters ?? '—' }}</dd>
            </div>

            <div class="grid grid-cols-[160px_1fr] gap-x-4 py-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider pt-0.5">YouTube URL</dt>
                <dd class="text-gray-600">
                    @if ($video->youtube_url)
                        <a href="{{ $video->youtube_url }}" target="_blank" class="text-blue-600 hover:underline">{{ $video->youtube_url }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>

        </dl>
    </div>

    {{-- Timestamps --}}
    <div class="text-xs text-gray-400 space-y-1">
        <p>Created: {{ $video->created_at->toFormattedDateString() }}</p>
        <p>Updated: {{ $video->updated_at->toFormattedDateString() }}</p>
    </div>

    {{-- Delete link --}}
    <div class="mt-8 pt-6 border-t border-gray-200">
        <a href="{{ route('videos.delete.confirm', $video) }}"
           class="text-sm text-red-500 hover:text-red-700 transition">Delete this video</a>
    </div>

</x-layouts.app>