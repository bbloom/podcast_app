<x-layouts.app title="Create New Episode — Details">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="3" />

    <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Episode Details</h1>
    <p class="text-center text-base text-gray-500 mb-8">Show: <strong>{{ $show->title }}</strong></p>

    @session('error')
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ $value }}</div>
    @endsession

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.create.step3.store') }}">
        @csrf

        {{-- Title --}}
        <div class="mb-5">
            <label for="title" class="block text-base font-semibold text-gray-700 mb-1">
                Title <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title"
                   value="{{ old('title') }}" autofocus
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('title') border-red-400 @enderror">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Episode number + Scheduled date --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label for="episode_number" class="block text-base font-semibold text-gray-700 mb-1">Episode #</label>
                <input type="number" id="episode_number" name="episode_number" min="1"
                       value="{{ old('episode_number') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('episode_number') border-red-400 @enderror">
                @error('episode_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="scheduled_date" class="block text-base font-semibold text-gray-700 mb-1">Scheduled Date</label>
                <input type="date" id="scheduled_date" name="scheduled_date"
                       value="{{ old('scheduled_date') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none @error('scheduled_date') border-red-400 @enderror">
                @error('scheduled_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.create.step2') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded font-semibold text-sm hover:bg-purple-800">
                Create Episode →
            </button>
        </div>

    </form>

</div>
</x-layouts.app>