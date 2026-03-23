<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 1</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Name your list</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 1])
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('lists.create.step1.submit') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-6">
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                List Name
            </label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="e.g. Morning Tech Digest"
                required
                autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('name') border-red-400 @enderror"
            >
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Choose a memorable name for this list, e.g. "Morning Tech Digest" or "Weekly News Roundup".</p>
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                Description <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <textarea
                id="description"
                name="description"
                rows="3"
                placeholder="e.g. A daily digest of tech news, YouTube videos, and podcast episodes."
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('description') border-red-400 @enderror"
            >{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Your own notes about what this list is for. Only you will see this.</p>
        </div>

        {{-- Timezone --}}
        <div class="mb-8">
            <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-2">
                Timezone <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <select
                id="timezone"
                name="timezone"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('timezone') border-red-400 @enderror"
            >
                <option value="">({{ auth()->user()->timezone }})</option>
                @foreach (\DateTimeZone::listIdentifiers() as $tz)
                    <option value="{{ $tz }}" {{ old('timezone') === $tz ? 'selected' : '' }}>
                        {{ $tz }}
                    </option>
                @endforeach
            </select>
            @error('timezone')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Only set this if you want this list to run on a different timezone than your account default.</p>
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Next Step...
            </button>
        </div>

    </form>

</x-layouts.app>
