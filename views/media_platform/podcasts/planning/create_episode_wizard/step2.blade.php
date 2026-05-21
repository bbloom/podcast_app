<x-layouts.app title="Create New Episode — Select Show">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="2" />

    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Select a Show</h1>

    @session('error')
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ $value }}</div>
    @endsession

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.create.step2.store') }}">
        @csrf

        <div class="space-y-3 mb-8">
            @forelse ($shows as $show)
                <label class="flex items-center gap-4 p-4 border border-purple-300 rounded-lg cursor-pointer hover:border-purple-400 bg-white hover:bg-purple-50 transition">
                    <input type="radio" name="podcast_show_id" value="{{ $show->id }}"
                           class="accent-purple-700 w-4 h-4"
                           {{ old('podcast_show_id') == $show->id ? 'checked' : '' }}
                    >
                    @if ($show->itunes_image)
                        <img src="{{ $show->itunes_image }}"
                                alt="{{ $show->title }}"
                                class="w-24 h-24 rounded object-cover border border-gray-200">
                    @else 
                        <span class="font-medium text-gray-800">{{ $show->title }}</span>
                    @endif
                </label>
            @empty
                <p class="text-gray-500 text-center py-6">No active shows found for your account.</p>
            @endforelse
        </div>

        @error('podcast_show_id')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-700 text-white rounded-lg hover:bg-purple-800 font-semibold">
                Next →
            </button>
        </div>
    </form>

</div>
</x-layouts.app>