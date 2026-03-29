<x-layouts.app title="New Podcast Show">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">Podcast Shows</a>
            <span>›</span>
            <span class="text-gray-700">New Show</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Podcast Show</h1>
    </div>

    <form method="POST" action="{{ route('podcast_shows.store') }}">
        @csrf

        {{-- ================================================================ --}}
        {{-- CORE                                                              --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-4">Core</h2>

        {{-- Title --}}
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
            @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- RSS Link --}}
        <div class="mb-6">
            <label for="rss_link" class="block text-sm font-semibold text-gray-700 mb-2">
                RSS Link <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="rss_link" name="rss_link" value="{{ old('rss_link') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('rss_link') border-red-400 @enderror">
            @error('rss_link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- ITUNES / APPLE PODCASTS                                          --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">iTunes / Apple Podcasts</h2>

        {{-- iTunes Image --}}
        <div class="mb-6">
            <label for="itunes_image" class="block text-sm font-semibold text-gray-700 mb-2">
                Cover Art URL <span class="font-normal text-gray-400">(optional — recommended 3000×3000px)</span>
            </label>
            <input type="url" id="itunes_image" name="itunes_image" value="{{ old('itunes_image') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_image') border-red-400 @enderror">
            @error('itunes_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Language --}}
        <div class="mb-6">
            <label for="itunes_language" class="block text-sm font-semibold text-gray-700 mb-2">
                Language <span class="font-normal text-gray-400">(ISO 639 code, e.g. "en")</span>
            </label>
            <input type="text" id="itunes_language" name="itunes_language" value="{{ old('itunes_language', 'en') }}" maxlength="10"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_language') border-red-400 @enderror">
            @error('itunes_language') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Category Primary --}}
        <div class="mb-6">
            <label for="itunes_category_primary" class="block text-sm font-semibold text-gray-700 mb-2">
                Primary Category <span class="font-normal text-gray-400">(optional — e.g. "Technology")</span>
            </label>
            <input type="text" id="itunes_category_primary" name="itunes_category_primary" value="{{ old('itunes_category_primary') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_category_primary') border-red-400 @enderror">
            @error('itunes_category_primary') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Category Secondary --}}
        <div class="mb-6">
            <label for="itunes_category_secondary" class="block text-sm font-semibold text-gray-700 mb-2">
                Secondary Category <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="itunes_category_secondary" name="itunes_category_secondary" value="{{ old('itunes_category_secondary') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_category_secondary') border-red-400 @enderror">
            @error('itunes_category_secondary') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Author --}}
        <div class="mb-6">
            <label for="itunes_author" class="block text-sm font-semibold text-gray-700 mb-2">
                Author <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="itunes_author" name="itunes_author" value="{{ old('itunes_author') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_author') border-red-400 @enderror">
            @error('itunes_author') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Link --}}
        <div class="mb-6">
            <label for="itunes_link" class="block text-sm font-semibold text-gray-700 mb-2">
                Podcast Website URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="itunes_link" name="itunes_link" value="{{ old('itunes_link') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_link') border-red-400 @enderror">
            @error('itunes_link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Owner Email --}}
        <div class="mb-6">
            <label for="itunes_email" class="block text-sm font-semibold text-gray-700 mb-2">
                Owner Email <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="email" id="itunes_email" name="itunes_email" value="{{ old('itunes_email') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_email') border-red-400 @enderror">
            @error('itunes_email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Owner Name --}}
        <div class="mb-6">
            <label for="itunes_name" class="block text-sm font-semibold text-gray-700 mb-2">
                Owner Name <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="itunes_name" name="itunes_name" value="{{ old('itunes_name') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_name') border-red-400 @enderror">
            @error('itunes_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Title --}}
        <div class="mb-6">
            <label for="itunes_title" class="block text-sm font-semibold text-gray-700 mb-2">
                iTunes Title Tag <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="itunes_title" name="itunes_title" value="{{ old('itunes_title') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_title') border-red-400 @enderror">
            @error('itunes_title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Type --}}
        <div class="mb-6">
            <label for="itunes_type" class="block text-sm font-semibold text-gray-700 mb-2">
                Podcast Type <span class="font-normal text-gray-400">(optional — default: episodic)</span>
            </label>
            <select id="itunes_type" name="itunes_type"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_type') border-red-400 @enderror">
                <option value="">— Select —</option>
                <option value="episodic" @selected(old('itunes_type', 'episodic') === 'episodic')>Episodic (default)</option>
                <option value="serial"   @selected(old('itunes_type') === 'serial')>Serial</option>
            </select>
            <p class="mt-1 text-xs text-gray-400">Episodic: standalone episodes. Serial: meant to be consumed in order.</p>
            @error('itunes_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Copyright --}}
        <div class="mb-6">
            <label for="itunes_copyright" class="block text-sm font-semibold text-gray-700 mb-2">
                Copyright <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="itunes_copyright" name="itunes_copyright" value="{{ old('itunes_copyright') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_copyright') border-red-400 @enderror">
            @error('itunes_copyright') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- New Feed URL --}}
        <div class="mb-6">
            <label for="itunes_new_feed_url" class="block text-sm font-semibold text-gray-700 mb-2">
                New Feed URL <span class="font-normal text-gray-400">(optional — use when migrating the feed URL)</span>
            </label>
            <input type="url" id="itunes_new_feed_url" name="itunes_new_feed_url" value="{{ old('itunes_new_feed_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_new_feed_url') border-red-400 @enderror">
            @error('itunes_new_feed_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Subtitle --}}
        <div class="mb-6">
            <label for="itunes_subtitle" class="block text-sm font-semibold text-gray-700 mb-2">
                Subtitle <span class="font-normal text-gray-400">(optional — brief tagline)</span>
            </label>
            <input type="text" id="itunes_subtitle" name="itunes_subtitle" value="{{ old('itunes_subtitle') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_subtitle') border-red-400 @enderror">
            @error('itunes_subtitle') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Summary --}}
        <div class="mb-6">
            <label for="itunes_summary" class="block text-sm font-semibold text-gray-700 mb-2">
                iTunes Summary <span class="font-normal text-gray-400">(optional — plain text)</span>
            </label>
            <textarea id="itunes_summary" name="itunes_summary" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('itunes_summary') border-red-400 @enderror">{{ old('itunes_summary') }}</textarea>
            @error('itunes_summary') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Content Encoded --}}
        <div class="mb-6">
            <label for="itunes_content_encoded" class="block text-sm font-semibold text-gray-700 mb-2">
                Content Encoded <span class="font-normal text-gray-400">(optional — HTML for &lt;content:encoded&gt;)</span>
            </label>
            <textarea id="itunes_content_encoded" name="itunes_content_encoded" rows="4"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('itunes_content_encoded') border-red-400 @enderror">{{ old('itunes_content_encoded') }}</textarea>
            @error('itunes_content_encoded') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Explicit --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Explicit Content</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_explicit" value="1"
                        {{ old('itunes_explicit', '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_explicit" value="0"
                        {{ old('itunes_explicit', '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
        </div>

        {{-- Block --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Block from Apple Podcasts</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_block" value="1"
                        {{ old('itunes_block', '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_block" value="0"
                        {{ old('itunes_block', '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
        </div>

        {{-- Complete --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mark Show as Complete</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_complete" value="1"
                        {{ old('itunes_complete', '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="itunes_complete" value="0"
                        {{ old('itunes_complete', '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- SPOTIFY                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Spotify</h2>

        {{-- Spotify Limit --}}
        <div class="mb-6">
            <label for="spotify_limit" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Limit <span class="font-normal text-gray-400">(optional — 0 = no limit)</span>
            </label>
            <input type="number" id="spotify_limit" name="spotify_limit" value="{{ old('spotify_limit', 0) }}" min="0"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('spotify_limit') border-red-400 @enderror">
            @error('spotify_limit') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Spotify Country --}}
        <div class="mb-6">
            <label for="spotify_country_of_origin" class="block text-sm font-semibold text-gray-700 mb-2">
                Country of Origin <span class="font-normal text-gray-400">(optional — default: global)</span>
            </label>
            <input type="text" id="spotify_country_of_origin" name="spotify_country_of_origin" value="{{ old('spotify_country_of_origin', 'global') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('spotify_country_of_origin') border-red-400 @enderror">
            @error('spotify_country_of_origin') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- WEBSITE                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Website</h2>

        {{-- Website Content --}}
        <div class="mb-6">
            <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">
                Page Content <span class="font-normal text-gray-400">(optional — full HTML)</span>
            </label>
            <textarea id="website_content" name="website_content" rows="6"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('website_content') border-red-400 @enderror">{{ old('website_content') }}</textarea>
            @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website Excerpt --}}
        <div class="mb-6">
            <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">
                Excerpt <span class="font-normal text-gray-400">(optional — shown in show listings)</span>
            </label>
            <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror">
            @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website Meta Description --}}
        <div class="mb-6">
            <label for="website_meta_description" class="block text-sm font-semibold text-gray-700 mb-2">
                Meta Description <span class="font-normal text-gray-400">(optional — SEO)</span>
            </label>
            <input type="text" id="website_meta_description" name="website_meta_description" value="{{ old('website_meta_description') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_meta_description') border-red-400 @enderror">
            @error('website_meta_description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Featured Image --}}
        <div class="mb-6">
            <label for="website_featured_image" class="block text-sm font-semibold text-gray-700 mb-2">
                Featured Image URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="website_featured_image" name="website_featured_image" value="{{ old('website_featured_image') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_featured_image') border-red-400 @enderror">
            @error('website_featured_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Publish On --}}
        <div class="mb-6">
            <label for="website_publish_on" class="block text-sm font-semibold text-gray-700 mb-2">Publish On</label>
            <input type="date" id="website_publish_on" name="website_publish_on" value="{{ old('website_publish_on', now()->toDateString()) }}"
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_publish_on') border-red-400 @enderror">
            @error('website_publish_on') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Website Enabled --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Visible on Website</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="website_enabled" value="1"
                        {{ old('website_enabled', '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="website_enabled" value="0"
                        {{ old('website_enabled', '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- STORAGE                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Storage</h2>

        {{-- Artwork URL --}}
        <div class="mb-6">
            <label for="storage_artwork_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Artwork Storage URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="storage_artwork_url" name="storage_artwork_url" value="{{ old('storage_artwork_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('storage_artwork_url') border-red-400 @enderror">
            @error('storage_artwork_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Video Files URL --}}
        <div class="mb-6">
            <label for="storage_video_files_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Video Files Storage URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="storage_video_files_url" name="storage_video_files_url" value="{{ old('storage_video_files_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('storage_video_files_url') border-red-400 @enderror">
            @error('storage_video_files_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Audio Files URL --}}
        <div class="mb-6">
            <label for="storage_audio_files_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Audio Files Storage URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="storage_audio_files_url" name="storage_audio_files_url" value="{{ old('storage_audio_files_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('storage_audio_files_url') border-red-400 @enderror">
            @error('storage_audio_files_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- ACTIONS                                                           --}}
        {{-- ================================================================ --}}
        <div class="flex items-center justify-end gap-3 mt-8">
            <a href="{{ route('podcast_shows.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Create Show
            </button>
        </div>

    </form>

</x-layouts.app>