<x-layouts.app title="Delete Planning Episode">
<div class="max-w-xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <p class="text-base text-gray-500 mb-4">
        <a href="{{ route('podcast_episodes_planning.index') }}" class="hover:underline text-purple-700">
            Planning Episodes
        </a>
        &rsaquo;
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}" class="hover:underline text-purple-700">
            {{ $episode->formatted_title }}
        </a>
        &rsaquo; Delete
    </p>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-12 mt-6 border border-red-800 bg-red-300 rounded-lg p-5">
        @if ($episode->show->itunes_image)
            <img src="{{ $episode->show->itunes_image }}"
                 alt="{{ $episode->show->title }}"
                 class="w-16 h-16 rounded object-cover border border-purple-200 flex-shrink-0">
        @endif
        <h1 class="text-3xl font-bold text-gray-800"><span class="text-2xl">delete:</span> {{ $episode->title }}</h1>
    </div>

    <div class="border border-red-300 rounded-lg overflow-hidden">
        <div class="bg-red-50 border-b border-red-300 px-5 py-3">
            <p class="text-base font-bold text-red-700">{{ $episode->formatted_title }}</p>
        </div>

        <div class="px-5 py-5 space-y-4">
            <p class="text-lg text-gray-800 pt-4">
                Are you sure you want to delete
                <strong>{{ $episode->formatted_title }}</strong>?
            </p>
  
            <p class="pt-6 font-bold">
                <ul class="space-y-2 text-lg text-red-600">
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true">⚠️</span>
                        <span>This is a permanent, hard delete.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true">⚠️</span>
                        <span>The planning record + all associated data will be removed immediately.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true">⚠️</span>
                        <span>No way to recover it.</span>
                    </li>
                </ul>
            </p>

            <div class="flex justify-center items-center gap-4 pt-12">
                <form method="POST" action="{{ route('podcast_episodes_planning.destroy', $episode) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-5 py-2 bg-red-700 text-white rounded font-semibold text-sm hover:bg-red-900">
                        Yes, Delete Permanently
                    </button>
                </form>
                <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
                   class="px-5 py-2 border border-green-800 text-gray-700 hover:text-white rounded font-semibold text-lg bg-green-300 hover:bg-green-800">
                    Cancel
                </a>
            </div>
        </div>
    </div>

</div>
</x-layouts.app>