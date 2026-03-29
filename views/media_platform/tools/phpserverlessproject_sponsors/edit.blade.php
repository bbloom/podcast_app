<x-layouts.app title="Edit Sponsor">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('phpserverlessproject_sponsors.index') }}" class="hover:text-purple-700 transition">PHPServerlessProject Sponsors</a>
            <span>›</span>
            <a href="{{ route('phpserverlessproject_sponsors.show', $sponsor) }}" class="hover:text-purple-700 transition">{{ $sponsor->full_name }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Sponsor</h1>
    </div>

    <form method="POST" action="{{ route('phpserverlessproject_sponsors.update', $sponsor) }}">
        @csrf
        @method('PUT')

        {{-- ================================================================ --}}
        {{-- PROFILE                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-4">Profile</h2>

        {{-- Full Name --}}
        <div class="mb-6">
            <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $sponsor->full_name) }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('full_name') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">First and last name.</p>
            @error('full_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Email Address --}}
        <div class="mb-6">
            <label for="email_address" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
            <input type="email" id="email_address" name="email_address" value="{{ old('email_address', $sponsor->email_address) }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('email_address') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Not related to application user accounts.</p>
            @error('email_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Profile Full --}}
        <div class="mb-6">
            <label for="profile_full" class="block text-sm font-semibold text-gray-700 mb-2">Full Profile</label>
            <textarea id="profile_full" name="profile_full" rows="6" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('profile_full') border-red-400 @enderror">{{ old('profile_full', $sponsor->profile_full) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Full biography or profile text.</p>
            @error('profile_full') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Profile Short --}}
        <div class="mb-6">
            <label for="profile_short" class="block text-sm font-semibold text-gray-700 mb-2">Short Profile</label>
            <input type="text" id="profile_short" name="profile_short" value="{{ old('profile_short', $sponsor->profile_short) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('profile_short') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">One-line tagline or summary, up to 255 characters.</p>
            @error('profile_short') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website --}}
        <div class="mb-6">
            <label for="link_to_sponsor_website" class="block text-sm font-semibold text-gray-700 mb-2">Website</label>
            <input type="url" id="link_to_sponsor_website" name="link_to_sponsor_website" value="{{ old('link_to_sponsor_website', $sponsor->link_to_sponsor_website) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('link_to_sponsor_website') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full URL including https://</p>
            @error('link_to_sponsor_website') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- IMAGES                                                            --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Images</h2>

        {{-- Image URL --}}
        <div class="mb-6">
            <label for="image_url" class="block text-sm font-semibold text-gray-700 mb-2">Image URL</label>
            <input type="url" id="image_url" name="image_url" value="{{ old('image_url', $sponsor->image_url) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('image_url') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Full-size profile image URL.</p>
            @error('image_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Thumbnail URL --}}
        <div class="mb-6">
            <label for="image_thumbnail_url" class="block text-sm font-semibold text-gray-700 mb-2">Thumbnail URL</label>
            <input type="url" id="image_thumbnail_url" name="image_thumbnail_url" value="{{ old('image_thumbnail_url', $sponsor->image_thumbnail_url) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('image_thumbnail_url') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Small thumbnail image URL.</p>
            @error('image_thumbnail_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- SPONSORSHIP                                                       --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Sponsorship</h2>

        {{-- Umbrella Sponsor --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Umbrella Sponsor</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="umbrella_sponsor" value="1"
                        {{ old('umbrella_sponsor', $sponsor->umbrella_sponsor ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="umbrella_sponsor" value="0"
                        {{ old('umbrella_sponsor', $sponsor->umbrella_sponsor ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
            @error('umbrella_sponsor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Basecamp Sponsor --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Basecamp Sponsor</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="basecamp_sponsor" value="1"
                        {{ old('basecamp_sponsor', $sponsor->basecamp_sponsor ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="basecamp_sponsor" value="0"
                        {{ old('basecamp_sponsor', $sponsor->basecamp_sponsor ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
            @error('basecamp_sponsor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Restream Sponsor --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Restream Sponsor</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="restream_sponsor" value="1"
                        {{ old('restream_sponsor', $sponsor->restream_sponsor ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="restream_sponsor" value="0"
                        {{ old('restream_sponsor', $sponsor->restream_sponsor ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
            @error('restream_sponsor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Former Sponsor --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Former Sponsor</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="former_sponsor" value="1"
                        {{ old('former_sponsor', $sponsor->former_sponsor ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="former_sponsor" value="0"
                        {{ old('former_sponsor', $sponsor->former_sponsor ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
            @error('former_sponsor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
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
                        {{ old('enabled', $sponsor->enabled ? '1' : '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Enabled</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="enabled" value="0"
                        {{ old('enabled', $sponsor->enabled ? '1' : '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Disabled</div>
                </label>
            </div>
            @error('enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Internal Comment --}}
        <div class="mb-8">
            <label for="internal_comment" class="block text-sm font-semibold text-gray-700 mb-2">Internal Comment</label>
            <textarea id="internal_comment" name="internal_comment" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('internal_comment') border-red-400 @enderror">{{ old('internal_comment', $sponsor->internal_comment) }}</textarea>
            <p class="mt-1 text-xs text-gray-400">Private notes — not published anywhere.</p>
            @error('internal_comment') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- ACTIONS                                                           --}}
        {{-- ================================================================ --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('phpserverlessproject_sponsors.delete.confirm', $sponsor) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this sponsor
            </a>
            <div class="flex gap-3">
                <a href="{{ route('phpserverlessproject_sponsors.show', $sponsor) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                    Save Changes
                </button>
            </div>
        </div>

    </form>

</x-layouts.app>