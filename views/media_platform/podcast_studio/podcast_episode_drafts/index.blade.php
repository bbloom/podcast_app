<x-layouts.app title="Podcast Episode Drafts">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Podcast Episode Drafts</h1>
        <a href="{{ route('podcast_episode_drafts.create.step1') }}"
           class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            + New Draft
        </a>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @if ($drafts->total() === 0)

        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500 mb-1">No drafts yet</p>
            <p class="text-sm text-gray-400 mb-6">Create your first episode draft to get started.</p>
            <a href="{{ route('podcast_episode_drafts.create.step1') }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Create a Draft
            </a>
        </div>

    @else

        @php
            $sortUrl = function (string $key) use ($sort, $dir) {
                $newDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
                return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $newDir, 'page' => 1]);
            };

            $sortIcon = function (string $key) use ($sort, $dir) {
                if ($sort !== $key) return '↕';
                return $dir === 'asc' ? '↑' : '↓';
            };
        @endphp

        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('id') }}" class="hover:text-purple-700 transition">
                                ID <span class="text-[10px]">{{ $sortIcon('id') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('show') }}" class="hover:text-purple-700 transition">
                                Show <span class="text-[10px]">{{ $sortIcon('show') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('episode_number') }}" class="hover:text-purple-700 transition">
                                Ep# <span class="text-[10px]">{{ $sortIcon('episode_number') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('title') }}" class="hover:text-purple-700 transition">
                                Title <span class="text-[10px]">{{ $sortIcon('title') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                            <a href="{{ $sortUrl('date') }}" class="hover:text-purple-700 transition">
                                Date <span class="text-[10px]">{{ $sortIcon('date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-400">
                    @foreach ($drafts as $draft)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500 tabular-nums">{{ $draft->id }}</td>
                            <td>
                                <a href="{{ route('podcast_shows.show', $draft->show) }}"
                                   class="hover:text-purple-700 transition">
                                    <img 
                                        src="{{ $draft->show->itunes_image }}" 
                                        alt="{{ $draft->show->title }}" 
                                        class="w-[75px] h-[75px] rounded-lg object-cover border border-gray-200 flex-shrink-0"
                                    >
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600 tabular-nums">{{ $draft->episode_number ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episode_drafts.show', $draft) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $draft->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $draft->date?->format('M d Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a 
                                    href="{{ route('podcast_episode_drafts.show', $draft) }}"
                                    class="inline-block bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition"
                                >
                                    Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($drafts->hasPages())
            <div class="mt-4">{{ $drafts->links() }}</div>
        @endif

    @endif

</x-layouts.app>