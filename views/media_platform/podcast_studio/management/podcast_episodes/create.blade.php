<x-layouts.app title="New Podcast Episode">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">Podcast Episodes</a>
            <span>›</span>
            <span class="text-gray-700">New Episode</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">New Podcast Episode</h1>
    </div>

    <div class="border border-purple-700 bg-purple-100 rounded-lg px-6 py-5 mb-8">
        <p class="text-xl font-semibold text-purple-700 mb-1">Although you can create a new episode here, you should create an episode with the wizard.</p>
    </div>

    <form method="POST" action="{{ route('podcast_episodes.store') }}">
        @csrf

        {{-- ================================================================ --}}
        {{-- CORE                                                              --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-4">Core</h2>

        {{-- Podcast Show --}}
        <div class="mb-6">
            <label for="podcast_show_id" class="block text-sm font-semibold text-gray-700 mb-2">Podcast Show</label>
            <select id="podcast_show_id" name="podcast_show_id" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('podcast_show_id') border-red-400 @enderror">
                <option value="">— Select a show —</option>
                @foreach ($shows as $show)
                    <option value="{{ $show->id }}" {{ old('podcast_show_id') == $show->id ? 'selected' : '' }}>
                        {{ $show->title }}
                    </option>
                @endforeach
            </select>
            @error('podcast_show_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Status --}}
        <div class="mb-6">
            <label for="podcast_episode_status_lookup_id" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
            <select id="podcast_episode_status_lookup_id" name="podcast_episode_status_lookup_id" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('podcast_episode_status_lookup_id') border-red-400 @enderror">
                <option value="">— Select a status —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}" {{ old('podcast_episode_status_lookup_id') == $status->id ? 'selected' : '' }}>
                        {{ $status->title }}
                    </option>
                @endforeach
            </select>
            @error('podcast_episode_status_lookup_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Title --}}
        <div class="mb-6">
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
            @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Scheduled Date --}}
        <div class="mb-6">
            <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-2">
                Scheduled Date <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="date" id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date') }}"
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('scheduled_date') border-red-400 @enderror">
            @error('scheduled_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Draft --}}
        <div class="mb-6">
            <label for="draft" class="block text-sm font-semibold text-gray-700 mb-2">
                Draft / Script <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <textarea id="draft" name="draft" rows="6"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('draft') border-red-400 @enderror">{{ old('draft') }}</textarea>
            @error('draft') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Raw Audio Filename --}}
        <div class="mb-6">
            <label for="raw_input_audio_filename" class="block text-sm font-semibold text-gray-700 mb-2">
                Raw Audio Filename <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="raw_input_audio_filename" name="raw_input_audio_filename" value="{{ old('raw_input_audio_filename') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('raw_input_audio_filename') border-red-400 @enderror">
            @error('raw_input_audio_filename') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ================================================================ --}}
        {{-- ITUNES / APPLE PODCASTS                                          --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">iTunes / Apple Podcasts</h2>

        {{-- Episode Number --}}
        <div class="mb-6">
            <label for="itunes_episode" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Number <span class="font-normal text-gray-400">(optional — 0 = not set)</span>
            </label>
            <input type="number" id="itunes_episode" name="itunes_episode" value="{{ old('itunes_episode', 0) }}" min="0"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_episode') border-red-400 @enderror">
            @error('itunes_episode') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Season Number --}}
        <div class="mb-6">
            <label for="itunes_season" class="block text-sm font-semibold text-gray-700 mb-2">
                Season Number <span class="font-normal text-gray-400">(optional — 0 = not set)</span>
            </label>
            <input type="number" id="itunes_season" name="itunes_season" value="{{ old('itunes_season', 0) }}" min="0"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_season') border-red-400 @enderror">
            @error('itunes_season') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Episode Type --}}
        <div class="mb-6">
            <label for="itunes_episode_type" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Type <span class="font-normal text-gray-400">(default: full)</span>
            </label>
            <select id="itunes_episode_type" name="itunes_episode_type"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_episode_type') border-red-400 @enderror">
                <option value="full"    @selected(old('itunes_episode_type', 'full') === 'full')>Full (default)</option>
                <option value="trailer" @selected(old('itunes_episode_type') === 'trailer')>Trailer</option>
                <option value="bonus"   @selected(old('itunes_episode_type') === 'bonus')>Bonus</option>
            </select>
            <p class="mt-1 text-xs text-gray-400">Full: complete episode. Trailer: preview. Bonus: extra content.</p>
            @error('itunes_episode_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Title Tag --}}
        <div class="mb-6">
            <label for="itunes_title_tag" class="block text-sm font-semibold text-gray-700 mb-2">
                iTunes Title Tag <span class="font-normal text-gray-400">(optional — if different from title)</span>
            </label>
            <input type="text" id="itunes_title_tag" name="itunes_title_tag" value="{{ old('itunes_title_tag') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_title_tag') border-red-400 @enderror">
            @error('itunes_title_tag') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enclosure URL --}}
        <div class="mb-6">
            <label for="itunes_enclosure_url" class="block text-sm font-semibold text-gray-700 mb-2">
                Enclosure URL <span class="font-normal text-gray-400">(optional — URL of the audio file)</span>
            </label>
            <input type="url" id="itunes_enclosure_url" name="itunes_enclosure_url" value="{{ old('itunes_enclosure_url') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_url') border-red-400 @enderror">
            @error('itunes_enclosure_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enclosure Length --}}
        <div class="mb-6">
            <label for="itunes_enclosure_length" class="block text-sm font-semibold text-gray-700 mb-2">
                Enclosure Length <span class="font-normal text-gray-400">(optional — file size in bytes)</span>
            </label>
            <input type="text" id="itunes_enclosure_length" name="itunes_enclosure_length" value="{{ old('itunes_enclosure_length') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_length') border-red-400 @enderror">
            @error('itunes_enclosure_length') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enclosure Type --}}
        <div class="mb-6">
            <label for="itunes_enclosure_type" class="block text-sm font-semibold text-gray-700 mb-2">
                Enclosure MIME Type <span class="font-normal text-gray-400">(optional — e.g. "audio/mpeg")</span>
            </label>
            <input type="text" id="itunes_enclosure_type" name="itunes_enclosure_type" value="{{ old('itunes_enclosure_type') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_type') border-red-400 @enderror">
            @error('itunes_enclosure_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- GUID --}}
        <div class="mb-6">
            <label for="itunes_guid" class="block text-sm font-semibold text-gray-700 mb-2">
                GUID <span class="font-normal text-gray-400">(optional — globally unique episode ID)</span>
            </label>
            <input type="text" id="itunes_guid" name="itunes_guid" value="{{ old('itunes_guid') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_guid') border-red-400 @enderror">
            @error('itunes_guid') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Pub Date --}}
        <div class="mb-6">
            <label for="itunes_pubdate" class="block text-sm font-semibold text-gray-700 mb-2">
                Pub Date <span class="font-normal text-gray-400">(optional — publication date/time)</span>
            </label>
            <input type="datetime-local" id="itunes_pubdate" name="itunes_pubdate" value="{{ old('itunes_pubdate') }}"
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_pubdate') border-red-400 @enderror">
            @error('itunes_pubdate') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Duration --}}
        <div class="mb-6">
            <label for="itunes_duration" class="block text-sm font-semibold text-gray-700 mb-2">
                Duration <span class="font-normal text-gray-400">(optional — e.g. "45:30" or "2730")</span>
            </label>
            <input type="text" id="itunes_duration" name="itunes_duration" value="{{ old('itunes_duration') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_duration') border-red-400 @enderror">
            @error('itunes_duration') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Link --}}
        <div class="mb-6">
            <label for="itunes_link" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Page URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="itunes_link" name="itunes_link" value="{{ old('itunes_link') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_link') border-red-400 @enderror">
            @error('itunes_link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Image --}}
        <div class="mb-6">
            <label for="itunes_image" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Cover Art URL <span class="font-normal text-gray-400">(optional — overrides show artwork)</span>
            </label>
            <input type="url" id="itunes_image" name="itunes_image" value="{{ old('itunes_image') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_image') border-red-400 @enderror">
            @error('itunes_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Description --}}
        <div class="mb-6">
            <label for="itunes_description" class="block text-sm font-semibold text-gray-700 mb-2">
                iTunes Description <span class="font-normal text-gray-400">(optional — plain text)</span>
            </label>
            <textarea id="itunes_description" name="itunes_description" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('itunes_description') border-red-400 @enderror">{{ old('itunes_description') }}</textarea>
            @error('itunes_description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- iTunes Subtitle --}}
        <div class="mb-6">
            <label for="itunes_subtitle" class="block text-sm font-semibold text-gray-700 mb-2">
                iTunes Subtitle <span class="font-normal text-gray-400">(optional — brief tagline)</span>
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
                Content Encoded <span class="font-normal text-gray-400">(optional — HTML show notes)</span>
            </label>
            <textarea id="itunes_content_encoded" name="itunes_content_encoded" rows="5"
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

        {{-- ================================================================ --}}
        {{-- RSS FEED                                                          --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">RSS Feed</h2>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Include in RSS Feed</label>
            <div class="flex gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="rss_feed_enabled" value="1"
                        {{ old('rss_feed_enabled', '0') === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">Yes</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="rss_feed_enabled" value="0"
                        {{ old('rss_feed_enabled', '0') === '0' ? 'checked' : '' }} class="sr-only peer">
                    <div class="border border-gray-300 rounded-lg px-4 py-3 text-sm font-semibold text-center text-gray-700 peer-checked:border-purple-700 peer-checked:text-purple-700 peer-checked:bg-purple-50 hover:border-gray-400 transition">No</div>
                </label>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- WEBSITE                                                           --}}
        {{-- ================================================================ --}}
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mt-10 mb-4">Website</h2>

        <div class="mb-6">
            <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">
                Page Content <span class="font-normal text-gray-400">(optional — full HTML)</span>
            </label>
            <textarea id="website_content" name="website_content" rows="6"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('website_content') border-red-400 @enderror">{{ old('website_content') }}</textarea>
            @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">
                Excerpt <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror">
            @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_meta_description" class="block text-sm font-semibold text-gray-700 mb-2">
                Meta Description <span class="font-normal text-gray-400">(optional — SEO)</span>
            </label>
            <input type="text" id="website_meta_description" name="website_meta_description" value="{{ old('website_meta_description') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_meta_description') border-red-400 @enderror">
            @error('website_meta_description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_episode_notes" class="block text-sm font-semibold text-gray-700 mb-2">
                Episode Notes <span class="font-normal text-gray-400">(optional — additional show notes)</span>
            </label>
            <textarea id="website_episode_notes" name="website_episode_notes" rows="4"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('website_episode_notes') border-red-400 @enderror">{{ old('website_episode_notes') }}</textarea>
            @error('website_episode_notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_attribution" class="block text-sm font-semibold text-gray-700 mb-2">
                Attribution <span class="font-normal text-gray-400">(optional — music, guests, credits)</span>
            </label>
            <textarea id="website_attribution" name="website_attribution" rows="3"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none @error('website_attribution') border-red-400 @enderror">{{ old('website_attribution') }}</textarea>
            @error('website_attribution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_featured_image" class="block text-sm font-semibold text-gray-700 mb-2">
                Featured Image URL <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <input type="url" id="website_featured_image" name="website_featured_image" value="{{ old('website_featured_image') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_featured_image') border-red-400 @enderror">
            @error('website_featured_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="website_publish_on" class="block text-sm font-semibold text-gray-700 mb-2">Publish On</label>
            <input type="date" id="website_publish_on" name="website_publish_on" value="{{ old('website_publish_on', now()->toDateString()) }}"
                class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_publish_on') border-red-400 @enderror">
            @error('website_publish_on') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

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
        {{-- ACTIONS                                                           --}}
        {{-- ================================================================ --}}
        <div class="flex items-center justify-end gap-3 mt-8">
            <a href="{{ route('podcast_episodes.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold px-5 py-3 transition">
                Cancel
            </a>
            <button type="submit"
                    class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition">
                Create Episode
            </button>
        </div>

    </form>

</x-layouts.app>