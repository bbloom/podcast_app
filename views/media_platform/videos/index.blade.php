<x-layouts.app title="Videos">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Videos</h1>
        <a href="{{ route('videos.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Video
        </a>
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

    @if ($videos->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-8 text-center text-sm text-gray-400">
            No videos yet. Create one to get started.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled Date</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($videos as $video)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500">{{ $video->id }}</td>
                            <td class="px-6 py-4 font-medium text-purple-700">
                                <a href="{{ route('videos.show', $video) }}"
                                   class="hover:underline">{{ $video->title }}</a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $video->scheduled_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('videos.show', $video) }}"
                                   class="text-xs text-purple-700 hover:underline">Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $videos->links() }}
        </div>
    @endif

</x-layouts.app>