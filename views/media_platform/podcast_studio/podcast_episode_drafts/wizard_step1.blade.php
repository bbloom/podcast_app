<x-layouts.app title="Create Episode Draft">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Create a Podcast Episode Draft</h1>
        @include('media_platform.podcast_studio.podcast_episode_drafts._step_dots', ['current' => 1])
        <p class="mt-3 text-sm text-gray-500">Step 1 of 2 — Select a podcast show</p>
    </div>

    @if (session('error'))
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Show list --}}
    <div class="border border-purple-500 rounded-lg overflow-hidden">
        @foreach ($shows as $show)
            <div class="flex items-center gap-6 px-6 py-4 bg-white {{ ! $loop->last ? 'border-b border-purple-500' : '' }}">

                {{-- Artwork --}}
                <div class="flex-shrink-0">
                    <img
                        src="{{ $show->itunes_image }}"
                        alt="{{ $show->title }}"
                        class="h-[100px] w-[100px] object-cover border border-gray-200 rounded"
                    >
                </div>

                {{-- Title + description --}}
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-gray-900 text-xl">{{ $show->title }}</div>
                    @if ($show->description)
                        <div class="mt-1 text-sm text-gray-500">{{ strip_tags($show->description) }}</div>
                    @endif
                </div>

                {{-- Select button --}}
                <div class="flex-shrink-0">
                    <form action="{{ route('podcast_episode_drafts.create.step1.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="podcast_show_id" value="{{ $show->id }}">
                        <button
                            type="submit"
                            class="bg-purple-900 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 text-white font-bold py-2 px-6 rounded-lg shadow transition"
                        >
                            Create a Draft
                        </button>
                    </form>
                </div>

            </div>
        @endforeach
    </div>

</x-layouts.app>