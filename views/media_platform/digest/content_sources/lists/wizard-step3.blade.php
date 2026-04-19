<x-layouts.app title="Create a List">

    {{-- Wizard header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create a List</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 mt-5">
            <span class="font-semibold text-purple-700">Step 3</span>
            <span>of 6</span>
            <span class="mx-2">—</span>
            <span>Choose how to deliver your digest</span>
        </div>
        @include('media_platform.digest.content_sources.lists._step_dots', ['current' => 3])
    </div>

    <form method="POST" action="{{ route('lists.create.step3.submit') }}">
        @csrf

        @error('output_type')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="flex flex-col gap-3 mb-8">

            {{-- Email --}}
            <label class="flex items-start gap-4 cursor-pointer group">
                <div class="flex-shrink-0 mt-1">
                    <input
                        type="radio"
                        name="output_type"
                        value="email"
                        {{ old('output_type') === 'email' ? 'checked' : '' }}
                        class="w-5 h-5 accent-purple-700 cursor-pointer"
                    >
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700 transition">Email</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        The digest is sent directly to your email as the full email body. No server or hosting required.
                    </p>
                </div>
            </label>

            {{-- Webpage (SFTP) --}}
            <label class="flex items-start gap-4 cursor-pointer group">
                <div class="flex-shrink-0 mt-1">
                    <input
                        type="radio"
                        name="output_type"
                        value="webpage"
                        {{ old('output_type') === 'webpage' ? 'checked' : '' }}
                        class="w-5 h-5 accent-purple-700 cursor-pointer"
                    >
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700 transition">Web Page (SFTP)</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        The digest is rendered as a standalone HTML page and uploaded to your web server via SFTP. You'll need an SFTP output destination configured.
                    </p>
                </div>
            </label>

            {{-- Static Site --}}
            <label class="flex items-start gap-4 cursor-pointer group">
                <div class="flex-shrink-0 mt-1">
                    <input
                        type="radio"
                        name="output_type"
                        value="static_site"
                        {{ old('output_type') === 'static_site' ? 'checked' : '' }}
                        class="w-5 h-5 accent-purple-700 cursor-pointer"
                    >
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700 transition">Static Site</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        The digest data is stored and served via the API. A deploy hook triggers your static site generator (Cloudflare Pages, Netlify, Vercel) to rebuild automatically. The static site fetches the data and renders pages on its own.
                    </p>
                </div>
            </label>

        </div>

        <div class="flex justify-between">
            <a href="{{ route('lists.create.step2') }}"
               class="text-sm text-gray-500 hover:text-gray-700 font-semibold py-3 transition">
                ← Back
            </a>
            <button
                type="submit"
                class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-6 py-3 rounded-lg transition"
            >
                Continue →
            </button>
        </div>
    </form>

</x-layouts.app>