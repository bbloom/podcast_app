<x-layouts.app title="New Podcast Guest">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_guests.index') }}" class="hover:text-purple-700 transition">Podcast Guests</a>
            <span>›</span>
            <span class="text-gray-700">New Guest</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Podcast Guest</h1>
    </div>

    <form method="POST" action="{{ route('podcast_guests.store') }}">
        @csrf

        {{-- ================================================================ --}}
        {{-- PROFILE                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-4">Profile</h2>

        {{-- Full Name --}}
        <div class="mb-6">
            <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('full_name') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">First and last name.</p>
            @error('full_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Email Address --}}
        <div class="mb-6">
            <label for="email_address" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
            <input type="email" id="email_address" name="email_address" value="{{ old('email_address') }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('email_address') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Not related to application user accounts.</p>
            @error('email_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Profile Full --}}
        <div class="mb-6">
            <label for="profile_full" class="block text-sm font-semibold text-gray-700 mb-2">Full Profile</label>
            <textarea id="profile_full" name="profile_full" rows="6" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('profile_full') border-red-400 @enderror">{{ old('profile_full') }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Full biography or profile text.</p>
            @error('profile_full') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Profile Short --}}
        <div class="mb-6">
            <label for="profile_short" class="block text-sm font-semibold text-gray-700 mb-2">
                Short Profile <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="profile_short" name="profile_short" value="{{ old('profile_short') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('profile_short') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">One-line tagline or summary, up to 255 characters.</p>
            @error('profile_short') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website --}}
        <div class="mb-6">
            <label for="link_to_guest_website" class="block text-sm font-semibold text-gray-700 mb-2">
                Website <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="link_to_guest_website" name="link_to_guest_website" value="{{ old('link_to_guest_website') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('link_to_guest_website') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full URL including https://</p>
            @error('link_to_guest_website') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- IMAGES                                                            --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Images</h2>

        {{-- Image URL --}}
        <div class="mb-6">
            <label for="image_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Image URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="image_url" name="image_url" value="{{ old('image_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('image_url') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full-size profile image URL.</p>
            @error('image_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Thumbnail URL --}}
        <div class="mb-6">
            <label for="image_thumbnail_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Thumbnail URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="image_thumbnail_url" name="image_thumbnail_url" value="{{ old('image_thumbnail_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('image_thumbnail_url') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Small thumbnail image URL.</p>
            @error('image_thumbnail_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- STATUS                                                            --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Status</h2>

        {{-- Enabled --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Enabled</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="1"
                        {{ old('enabled', '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Enabled</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', '1') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Disabled</div>
                </label>
            </div>
            @error('enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Internal Comment --}}
        <div class="mb-8">
            <label for="internal_comment" class="block text-sm font-semibold text-gray-700 mb-2">
                Internal Comment <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <textarea id="internal_comment" name="internal_comment" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('internal_comment') border-red-400 @enderror">{{ old('internal_comment') }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Private notes — not published anywhere.</p>
            @error('internal_comment') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 mt-8">
            <a href="{{ route('podcast_guests.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Create Guest
            </button>
        </div>

    </form>

</x-layouts.app>