<x-layouts.app title="Edit Podcast Episode">

    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('podcast_episodes.index') }}" class="hover:text-purple-700 transition">← Episodes</a>
            <span>›</span>
            <a href="{{ route('podcast_episodes.show', $episode) }}" class="hover:text-purple-700 transition">{{ $episode->title }}</a>
            <span>›</span>
            <span class="text-gray-700">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Edit Podcast Episode</h1>
    </div>

    <form method="POST" action="{{ route('podcast_episodes.update', $episode) }}">
        @csrf
        @method('PUT')

        {{-- ================================================================ --}}
        {{-- GENERAL                                                           --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">General</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            {{-- Podcast Show --}}
            <div class="mb-6">
                <label for="podcast_show_id" class="block text-sm font-semibold text-gray-700 mb-2">Podcast Show</label>
                <select id="podcast_show_id" name="podcast_show_id" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('podcast_show_id') border-red-400 @enderror">
                    <option value="">— Select a show —</option>
                    @foreach ($shows as $show)
                        <option value="{{ $show->id }}" {{ old('podcast_show_id', $episode->podcast_show_id) == $show->id ? 'selected' : '' }}>
                            {{ $show->title }}
                        </option>
                    @endforeach
                </select>
                @error('podcast_show_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Title --}}
            <div class="mb-6">
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $episode->title) }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('title') border-red-400 @enderror">
                @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>This title is for the website.</li>
                    <li>This title displays on the listing in this admin.</li>
                    <li>This title is NOT for the RSS Feed.</li>
                    <li>Be clear and concise.</li>
                    <li>Do not repeat the title of your show within your episode title.</li>
                    <li>Must be unique.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Slug --}}
            <div class="mb-6">
                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">Slug</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $episode->slug) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('slug') border-red-400 @enderror">
                @error('slug') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>We want to use the Title in the URL. But, we cannot use the Title as-is. So, we re-work it a little. I do not know why this is called a slug!</li>
                    <li>To automatically generate a slug, just leave this field blank.</li>
                    <li>If you want to create your own slug, I recommend that you use underscores instead of spaces, use small caps only, streamline the title for the slug whenever possible, and do not use special characters. The goal is to concisely convey the title in the URL.</li>
                </ul>
            </div>

            {{-- Scheduled Date --}}
            <div class="mb-6">
                <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-2">Scheduled Date</label>
                <input type="date" id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date', $episode->scheduled_date?->toDateString()) }}"
                    class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('scheduled_date') border-red-400 @enderror">
                @error('scheduled_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>What date is this episode scheduled?</li>
                    <li>optional, sort of</li>
                </ul>
            </div>

            {{-- Raw Audio Filename --}}
            <div class="mb-6">
                <label for="raw_input_audio_filename" class="block text-sm font-semibold text-gray-700 mb-2">Raw Audio Filename</label>
                <input type="text" id="raw_input_audio_filename" name="raw_input_audio_filename" value="{{ old('raw_input_audio_filename', $episode->raw_input_audio_filename) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('raw_input_audio_filename') border-red-400 @enderror">
                @error('raw_input_audio_filename') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>This is the name of the audio file you recorded that will be used to create the final production podcast file.</li>
                <li>This filename must include the extension.</li>
                <li>The post-production process populates this field automatically, so you can ignore this field. However, if you need to specify this filename manually, then please do so here.</li>
                <li>Optional.</li>
            </ul>
                        </div>

            {{-- Draft / Script --}}
            <div class="mb-0">
                <label for="draft" class="block text-sm font-semibold text-gray-700 mb-2">Draft / Script</label>
                <textarea id="draft" name="draft" rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('draft') border-red-400 @enderror">{{ old('draft', $episode->draft) }}</textarea>
                @error('draft') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Optional. Future use for creating podcast transcript.</li>
                </ul>

            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- STATUS                                                            --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Status</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select id="status" name="status" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('status') border-red-400 @enderror">
                    <option value="">— Select a status —</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $episode->status?->value) === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- APPLE / ITUNES                                                    --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Apple / iTunes</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            {{-- Primary Title --}}
            <div class="mb-6">
                <label for="itunes_title_tag" class="block text-sm font-semibold text-gray-700 mb-2">Primary Title</label>
                <input type="text" id="itunes_title_tag" name="itunes_title_tag" value="{{ old('itunes_title_tag', $episode->itunes_title_tag) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_title_tag') border-red-400 @enderror">
                @error('itunes_title_tag') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Some help notes are directly from <a href="https://help.apple.com/itc/podcasts_connect/#/itcb54353390" target="_blank" class="text-purple-700 hover:underline">https://help.apple.com/itc/podcasts_connect/#/itcb54353390</a> (opens new window).</li>
                    <li>The clear concise name for this episode.</li>
                    <li>If you want to include the season and episode numbers in this title, use the "S02 EP04 Episode Title" format.</li>
                    <li>Do not repeat the title of your show within your episode title.</li>
                    <li>If you want this title to be the same as the title above, then leave this field blank.</li>
                </ul>
            </div>

            {{-- Description --}}
            <div class="mb-6">
                <label for="itunes_description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="itunes_description" name="itunes_description" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('itunes_description') border-red-400 @enderror">{{ old('itunes_description', $episode->itunes_description) }}</textarea>
                @error('itunes_description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>One or more sentences describing your episode.</li>
                    <li>You can specify up to 10,000 characters (bytes).</li>
                    <li>You can use rich text formatting and some HTML tags ("p", "ol", "ul", "li", "a").</li>
                    <li>As a practical matter, there is not a lot of real estate on the podcast directories for a long description. A full description, but not a long description.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Summary --}}
            <div class="mb-6">
                <label for="itunes_summary" class="block text-sm font-semibold text-gray-700 mb-2">Summary</label>
                <textarea id="itunes_summary" name="itunes_summary" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('itunes_summary') border-red-400 @enderror">{{ old('itunes_summary', $episode->itunes_summary) }}</textarea>
                @error('itunes_summary') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>A paragraph describing the subject of your episode.</li>
                    <li>Plain text only.</li>
                    <li>Generally, the DESCRIPTION = SUMMARY (except for the HTML).</li>
                </ul>
            </div>

            {{-- Content Encoded --}}
            <div class="mb-6">
                <label for="itunes_content_encoded" class="block text-sm font-semibold text-gray-700 mb-2">Content Encoded</label>
                <textarea id="itunes_content_encoded" name="itunes_content_encoded" rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('itunes_content_encoded') border-red-400 @enderror">{{ old('itunes_content_encoded', $episode->itunes_content_encoded) }}</textarea>
                @error('itunes_content_encoded') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Similar to DESCRIPTION, allows HTML.</li>
                    <li>Encode HTML — see <a href="https://www.toptal.com/designers/htmlarrows" target="_blank" class="text-purple-700 hover:underline">toptal.com</a> (opens new window).</li>
                    <li>Encode HTML — see <a href="https://www.w3schools.com/html/html_entities.asp" target="_blank" class="text-purple-700 hover:underline">w3schools.com</a> (opens new window).</li>
                    <li>Do not encode <code>&lt;</code> and <code>&gt;</code> when part of the actual HTML tag (e.g. a link).</li>
                    <li>Generally, DESCRIPTION = CONTENT:ENCODED + encoding ampersands etc. However, more links and additional content are sometimes included in CONTENT:ENCODED than in DESCRIPTION.</li>
                    <li>This field is not washed, so enter content precisely!</li>
                    <li>Podcast show: 4,000 bytes. Podcast episode: 10,000 bytes.</li>
                </ul>
                            </div>

            {{-- Subtitle --}}
            <div class="mb-6">
                <label for="itunes_subtitle" class="block text-sm font-semibold text-gray-700 mb-2">Subtitle</label>
                <input type="text" id="itunes_subtitle" name="itunes_subtitle" value="{{ old('itunes_subtitle', $episode->itunes_subtitle) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_subtitle') border-red-400 @enderror">
                @error('itunes_subtitle') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>A quick one line summary of your podcast's episode.</li>
                </ul>
            </div>

            {{-- Media File URL --}}
            <div class="mb-6">
                <label for="itunes_enclosure_url" class="block text-sm font-semibold text-gray-700 mb-2">Media File URL</label>
                <input type="url" id="itunes_enclosure_url" name="itunes_enclosure_url" value="{{ old('itunes_enclosure_url', $episode->itunes_enclosure_url) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_url') border-red-400 @enderror">
                @error('itunes_enclosure_url') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The URL attribute points to your podcast media file.</li>
                    <li>Supported file formats include M4A, MP3, MOV, MP4, M4V, and PDF (yes, the docs say "PDF").</li>
                    <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
                </ul>
            </div>

            {{-- Media File Size --}}
            <div class="mb-6">
                <label for="itunes_enclosure_length" class="block text-sm font-semibold text-gray-700 mb-2">Media File Size</label>
                <input type="text" id="itunes_enclosure_length" name="itunes_enclosure_length" value="{{ old('itunes_enclosure_length', $episode->itunes_enclosure_length) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_length') border-red-400 @enderror">
                @error('itunes_enclosure_length') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                <li>What is the size of the episode's media file?</li>
                <li>Please specify the file size in bytes. e.g. if your MP3 is 20MB, then specify 20000000 (no commas!).</li>
                <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
            </ul>
            </div>

            {{-- Media File Type --}}
            <div class="mb-6">
                <label for="itunes_enclosure_type" class="block text-sm font-semibold text-gray-700 mb-2">Media File Type</label>
                <select id="itunes_enclosure_type" name="itunes_enclosure_type"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_enclosure_type') border-red-400 @enderror">
                    <option value="">— Select a type —</option>
                    @foreach ([
                        'audio/x-m4a'     => 'audio/x-m4a',
                        'audio/mpeg'      => 'audio/mpeg',
                        'video/quicktime' => 'video/quicktime',
                        'video/mp4'       => 'video/mp4',
                        'video/x-m4v'     => 'video/x-m4v',
                        'application/pdf' => 'application/pdf',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('itunes_enclosure_type', $episode->itunes_enclosure_type) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('itunes_enclosure_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
                </ul>
            </div>

            {{-- GUID --}}
            <div class="mb-6">
                <label for="itunes_guid" class="block text-sm font-semibold text-gray-700 mb-2">GUID</label>
                <input type="text" id="itunes_guid" name="itunes_guid" value="{{ old('itunes_guid', $episode->itunes_guid) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_guid') border-red-400 @enderror">
                @error('itunes_guid') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
                </ul>
            </div>

            {{-- Publish Date --}}
            <div class="mb-6">
                <label for="itunes_pubdate" class="block text-sm font-semibold text-gray-700 mb-2">Publish Date</label>
                <input type="datetime-local" id="itunes_pubdate" name="itunes_pubdate"
                    value="{{ old('itunes_pubdate', $episode->itunes_pubdate?->format('Y-m-d\TH:i')) }}"
                    class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_pubdate') border-red-400 @enderror">
                @error('itunes_pubdate') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The date and time when an episode was released.</li>
                    <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
                </ul>
            </div>

            {{-- Duration --}}
            <div class="mb-6">
                <label for="itunes_duration" class="block text-sm font-semibold text-gray-700 mb-2">Duration</label>
                <input type="text" id="itunes_duration" name="itunes_duration" value="{{ old('itunes_duration', $episode->itunes_duration) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_duration') border-red-400 @enderror">
                @error('itunes_duration') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-inside">
                    <li>Total amount of time of your podcast.</li>
                    <li>Apple Podcasts recommends reporting total seconds.</li>
                    <li>I recommend using the "HH:MM:SS" format. e.g. 01:37:46, 00:32:54.</li>
                    <li>This field is required when it is time to include this episode in the show's RSS feed.</li>
                </ul>
            </div>

            {{-- Website Link --}}
            <div class="mb-6">
                <label for="itunes_link" class="block text-sm font-semibold text-gray-700 mb-2">Website Link</label>
                <input type="url" id="itunes_link" name="itunes_link" value="{{ old('itunes_link', $episode->itunes_link) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_link') border-red-400 @enderror">
                @error('itunes_link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Full link to your episode's webpage.</li>
                </ul>
            </div>

            {{-- Image --}}
            <div class="mb-6">
                <label for="itunes_image" class="block text-sm font-semibold text-gray-700 mb-2">Image</label>
                <input type="url" id="itunes_image" name="itunes_image" value="{{ old('itunes_image', $episode->itunes_image) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_image') border-red-400 @enderror">
                @error('itunes_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The episode's artwork location.</li>
                    <li>Use this tag when you have a high quality, episode-specific image you would like listeners to see.</li>
                    <li>Leave this field blank when you have no episode-specific artwork.</li>
                    <li>Overrides show artwork.</li>
                </ul>
            </div>

            {{-- Explicit --}}
            <div class="mb-6">
                <label for="itunes_explicit" class="block text-sm font-semibold text-gray-700 mb-2">Explicit Content</label>
                <select id="itunes_explicit" name="itunes_explicit"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_explicit') border-red-400 @enderror">
                    <option value="0" @selected(! old('itunes_explicit', $episode->itunes_explicit))>No</option>
                    <option value="1" @selected((bool) old('itunes_explicit', $episode->itunes_explicit))>Yes</option>
                </select>
                @error('itunes_explicit') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Yes: Presence of explicit content. Apple Podcasts displays an Explicit parental advisory graphic for your podcast. Podcasts containing explicit material aren't available in some Apple Podcasts territories.</li>
                    <li>No: Does not contain explicit language or adult content. Apple Podcasts displays a Clean parental advisory graphic for your podcast.</li>
                    <li>Required.</li>
                </ul>
                            </div>

            {{-- Secondary Title --}}
            <div class="mb-6">
                <label for="itunes_itunestitle_tag" class="block text-sm font-semibold text-gray-700 mb-2">Secondary Title</label>
                <input type="text" id="itunes_itunestitle_tag" name="itunes_itunestitle_tag" value="{{ old('itunes_itunestitle_tag', $episode->itunes_itunestitle_tag) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_itunestitle_tag') border-red-400 @enderror">
                @error('itunes_itunestitle_tag') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>An alternate title tag that you should use when you have season and episode numbers.</li>
                    <li>Do not specify the episode number or season number in this tag.</li>
                    <li>Leave this field blank when you want it to be the same as the primary title.</li>
                </ul>
            </div>

            {{-- Episode Number --}}
            <div class="mb-6">
                <label for="itunes_episode" class="block text-sm font-semibold text-gray-700 mb-2">Episode Number</label>
                <input type="number" id="itunes_episode" name="itunes_episode" value="{{ old('itunes_episode', $episode->itunes_episode) }}" min="0"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_episode') border-red-400 @enderror">
                @error('itunes_episode') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>If all your episodes have numbers and you would like them to be ordered based on them, use this tag for each one.</li>
                    <li>Using episode number is mandatory for "Serial" podcast shows.</li>
                    <li>Specify 0 (zero) when not applicable.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Season Number --}}
            <div class="mb-6">
                <label for="itunes_season" class="block text-sm font-semibold text-gray-700 mb-2">Season Number</label>
                <input type="number" id="itunes_season" name="itunes_season" value="{{ old('itunes_season', $episode->itunes_season) }}" min="0"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_season') border-red-400 @enderror">
                @error('itunes_season') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>The episode season number.</li>
                    <li>Specify 0 (zero) when not applicable.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Episode Type --}}
            <div class="mb-6">
                <label for="itunes_episode_type" class="block text-sm font-semibold text-gray-700 mb-2">Episode Type</label>
                <select id="itunes_episode_type" name="itunes_episode_type"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_episode_type') border-red-400 @enderror">
                    <option value="full"    @selected(old('itunes_episode_type', $episode->itunes_episode_type) === 'full')>Full</option>
                    <option value="trailer" @selected(old('itunes_episode_type', $episode->itunes_episode_type) === 'trailer')>Trailer</option>
                    <option value="bonus"   @selected(old('itunes_episode_type', $episode->itunes_episode_type) === 'bonus')>Bonus</option>
                </select>
                @error('itunes_episode_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Full: when this episode represents the complete content.</li>
                    <li>Trailer: short, promotional content that represents a preview of your current show.</li>
                    <li>Bonus: extra content for your show, such as behind the scenes information or interviews with the cast; or, cross-promotional content for another show.</li>
                    <li>Required.</li>
                </ul>
            </div>

            {{-- Block --}}
            <div class="mb-0">
                <label for="itunes_block" class="block text-sm font-semibold text-gray-700 mb-2">Block from Apple Podcasts</label>
                <select id="itunes_block" name="itunes_block"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('itunes_block') border-red-400 @enderror">
                    <option value="0" @selected(! old('itunes_block', $episode->itunes_block))>No</option>
                    <option value="1" @selected((bool) old('itunes_block', $episode->itunes_block))>Yes</option>
                </select>
                @error('itunes_block') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>If you want an episode removed from the Apple directory, select "Yes".</li>
                    <li>You might want to block a specific episode if you know that its content would otherwise cause the entire podcast to be removed from Apple Podcasts.</li>
                    <li>Required.</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- RSS FEED                                                          --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">RSS Feed</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            <div class="mb-0">
                <label for="rss_feed_enabled" class="block text-sm font-semibold text-gray-700 mb-2">Include in RSS Feed</label>
                <select id="rss_feed_enabled" name="rss_feed_enabled"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('rss_feed_enabled') border-red-400 @enderror">
                    <option value="0" @selected(! old('rss_feed_enabled', $episode->rss_feed_enabled))>No</option>
                    <option value="1" @selected((bool) old('rss_feed_enabled', $episode->rss_feed_enabled))>Yes</option>
                </select>
                @error('rss_feed_enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Do you want this podcast episode included in the RSS feed?</li>
                </ul>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- WEBSITE                                                           --}}
        {{-- ================================================================ --}}
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Website</div>
        <div class="border border-purple-500 rounded-lg p-6 mb-8">

            {{-- Content --}}
            <div class="mb-6">
                <label for="website_content" class="block text-sm font-semibold text-gray-700 mb-2">Content</label>
                <textarea id="website_content" name="website_content" rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_content') border-red-400 @enderror">{{ old('website_content', $episode->website_content) }}</textarea>
                @error('website_content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>HTML is fine.</li>
                </ul>
            </div>

            {{-- Excerpt --}}
            <div class="mb-6">
                <label for="website_excerpt" class="block text-sm font-semibold text-gray-700 mb-2">Excerpt</label>
                <input type="text" id="website_excerpt" name="website_excerpt" value="{{ old('website_excerpt', $episode->website_excerpt) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_excerpt') border-red-400 @enderror">
                @error('website_excerpt') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Excerpt for the website page</li>
                </ul>
            </div>

            {{-- Meta Description --}}
            <div class="mb-6">
                <label for="website_meta_description" class="block text-sm font-semibold text-gray-700 mb-2">Meta Description</label>
                <input type="text" id="website_meta_description" name="website_meta_description" value="{{ old('website_meta_description', $episode->website_meta_description) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_meta_description') border-red-400 @enderror">
                @error('website_meta_description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>For the website's meta-description tag</li>
                </ul>
            </div>

            {{-- Notes --}}
            <div class="mb-6">
                <label for="website_episode_notes" class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                <textarea id="website_episode_notes" name="website_episode_notes" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_episode_notes') border-red-400 @enderror">{{ old('website_episode_notes', $episode->website_episode_notes) }}</textarea>
                @error('website_episode_notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Notes from the episode that you want placed in its own section on the website's episode page.</li>
                </ul>
            </div>

            {{-- Attribution --}}
            <div class="mb-6">
                <label for="website_attribution" class="block text-sm font-semibold text-gray-700 mb-2">Attribution</label>
                <textarea id="website_attribution" name="website_attribution" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize @error('website_attribution') border-red-400 @enderror">{{ old('website_attribution', $episode->website_attribution) }}</textarea>
                @error('website_attribution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Do you need to attribute music (including intros and outros), audio, or anything else that you played in this episode? List them here.</li>
                </ul>
            </div>

            {{-- Featured Image --}}
            <div class="mb-6">
                <label for="website_featured_image" class="block text-sm font-semibold text-gray-700 mb-2">Featured Image URL</label>
                <input type="url" id="website_featured_image" name="website_featured_image" value="{{ old('website_featured_image', $episode->website_featured_image) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_featured_image') border-red-400 @enderror">
                @error('website_featured_image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Full URL of the featured image for the website.</li>
                </ul>
            </div>

            {{-- Publish On --}}
            <div class="mb-6">
                <label for="website_publish_on" class="block text-sm font-semibold text-gray-700 mb-2">Publish On</label>
                <input type="date" id="website_publish_on" name="website_publish_on" value="{{ old('website_publish_on', $episode->website_publish_on?->toDateString()) }}"
                    class="border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_publish_on') border-red-400 @enderror">
                @error('website_publish_on') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-600 list-disc list-outside pl-5">
                    <li>Display on, and after, this date.</li>
                </ul>
            </div>

            {{-- Visible on Website --}}
            <div class="mb-0">
                <label for="website_enabled" class="block text-sm font-semibold text-gray-700 mb-2">Visible on Website</label>
                <select id="website_enabled" name="website_enabled"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition @error('website_enabled') border-red-400 @enderror">
                    <option value="0" @selected(! old('website_enabled', $episode->website_enabled))>No</option>
                    <option value="1" @selected((bool) old('website_enabled', $episode->website_enabled))>Yes</option>
                </select>
                @error('website_enabled') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- ACTIONS                                                           --}}
        {{-- ================================================================ --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('podcast_episodes.delete.confirm', $episode) }}"
               class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                Delete this episode
            </a>
            <div class="flex gap-3">
                <a href="{{ route('podcast_episodes.show', $episode) }}"
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