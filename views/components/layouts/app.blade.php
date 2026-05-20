<!DOCTYPE html>
<html lang="en">
<x-layouts.head :title="($title ?? 'App') . (config('app.env') !== 'production' ? ' [' . config('app.env') . ']' : '')" />

<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow p-4 flex justify-between items-center">
        <div class="flex items-center">
            <img class="fill-current h-16 w-16 mr-2" src="/favicons/visage-logo_150x150.png" width="54" height="54" />
            <a href="/" class="font-semibold text-lg">
                @php
                    echo config('app.name');
                    if (config('app.env') != 'production') {
                        echo ' [' . strtoupper(config('app.env')) . ']';
                    }
                @endphp
            </a>
        </div>

        <div class="flex items-center space-x-6">
            @auth
                @can('admin')
                {{-- LLM Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-lg text-purple-700 hover:text-gray-900 focus:outline-none">
                        <span class="font-medium">Config</span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="{{ route('language_models.providers.index') }}" class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Providers</a>
                        <a href="{{ route('language_models.languagemodel.index') }}" class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Language Models</a>
                        <a href="{{ route('language_models.usecases.index') }}" class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Use Cases</a>
                    </div>
                </div>
                @endcan

                {{-- Digest Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-lg text-purple-700 hover:text-gray-900 focus:outline-none">
                        <span class="font-medium">Digest</span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="{{ route('lists.index') }}"                class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Lists</a>
                        <a href="{{ route('output_destinations.index') }}"  class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Destinations</a>
                        <a href="{{ route('digest-podcasts.index') }}"      class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Podcasts</a>
                        <a href="{{ route('text_based_rss_feeds.index') }}" class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Regular RSS</a>
                        <a href="{{ route('youtube.channels.index') }}"     class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">YouTube</a>
                    </div>
                </div>

                {{-- Podcasts Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-lg text-purple-700 hover:text-gray-900 focus:outline-none">
                        <span class="font-medium">Podcasts</span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="{{ route('podcasts.dashboard') }}"                             class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Dashboard</a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('podcast_episodes_planning.index') }}"                class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Planning Episodes</a>
                        <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"  class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Create New Episode</a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('podcast_episodes.index') }}"                         class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Published Episodes</a>
                        <a href="{{ route('podcast_shows.index') }}"                            class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Shows</a>
                        <a href="{{ route('podcast_guests.index') }}"                           class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Guests</a>
                        <a href="{{ route('podcast_links.index') }}"                            class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Links</a>
                    </div>
                </div>

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2 text-lg text-purple-700 hover:text-gray-900 focus:outline-none">
                        <span class="font-medium">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition
                         style="display: none;"
                         class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="{{ route('dashboard') }}"   class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Dashboard</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-lg text-gray-700 hover:bg-gray-50">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a class="text-blue-600 hover:underline hover:text-gray-900 pr-10" href="{{ route('login') }}">Login</a>
            @endauth
        </div>
    </nav>

    <main class="max-w-5xl mx-auto py-8 px-4">
        {{ $slot }}
    </main>

    <x-layouts.footer />

@stack('scripts')
</body>
</html>