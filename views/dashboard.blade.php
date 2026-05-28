<x-layouts.app title="Dashboard">

    <h1 class="text-2xl font-bold mb-2">Dashboard</h1>
    <p class="text-lg mb-8">Welcome, <strong>{{ auth()->user()->name }}</strong>!</p>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        {{-- ================================================================ --}}
        {{-- LEFT COLUMN                                                      --}}
        {{-- ================================================================ --}}
        <div class="space-y-6">

            {{-- Podcasts --}}
            <div class="border border-purple-300 rounded-lg overflow-hidden">
                <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                    🎙 Podcasts
                </h2>
                <div class="space-y-5 p-4">

                    <div>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('podcasts.dashboard') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">Main Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Digest --}}
            <div class="border border-purple-300 rounded-lg overflow-hidden">
                <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                    📋 Digest
                </h2>
                <div class="space-y-5 p-4">

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lists and Destinations</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('lists.index') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">My Lists</a>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('output_destinations.index') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">Output Destinations</a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Content Sources</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('youtube.channels.index') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">Youtube Channels</a>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('digest-podcasts.index') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">Podcasts</a>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('text_based_rss_feeds.index') }}"
                                   class="text-blue-600 hover:underline hover:text-gray-900">Text Based RSS Feeds</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Videos --}}
            <div class="border border-purple-300 rounded-lg overflow-hidden">
                <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                    🎬 Videos
                </h2>
                <div class="space-y-5 p-4">

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Create</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('videos.create.step1') }}"
                                class="text-blue-600 hover:underline hover:text-gray-900">Create a video</a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-400 font-bold">›</span>
                                <a href="{{ route('videos.index') }}"
                                class="text-blue-600 hover:underline hover:text-gray-900">All Videos</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- RIGHT COLUMN                                                     --}}
        {{-- ================================================================ --}}
        <div class="space-y-6">

            {{-- Account --}}
            <div class="border border-purple-300 rounded-lg overflow-hidden">
                <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                    👤 Account
                </h2>
                <div class="p-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-purple-400 font-bold">›</span>
                        <a href="/account/settings"
                           class="text-blue-600 hover:underline hover:text-gray-900">Account Settings</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-purple-400 font-bold">›</span>
                        <a href="{{ route('profile.edit') }}"
                           class="text-blue-600 hover:underline hover:text-gray-900">Profile</a>
                    </div>
                </div>
            </div>

            @can('admin')

                {{-- Configuration --}}
                <div class="border border-purple-300 rounded-lg overflow-hidden">
                    <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                        ⚙️ Configuration
                    </h2>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('language_models.providers.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Providers</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('language_models.languagemodel.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Language Models</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('language_models.usecases.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Use Cases</a>
                        </div>
                    </div>
                </div>

                {{-- API Management --}}
                <div class="border border-purple-300 rounded-lg overflow-hidden">
                    <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                        🔌 API Management
                    </h2>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('api_management.dashboard') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">API Dashboard</a>
                        </div>
                    </div>
                </div>

                {{-- Tools --}}
                <div class="border border-purple-300 rounded-lg overflow-hidden">
                    <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                        🔧 Tools
                    </h2>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="/adhocprompt"
                               class="text-blue-600 hover:underline hover:text-gray-900">Ad Hoc Prompt</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('admin.database-backups.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Database Backups</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('admin.health-checks.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Health Checks</a>
                            <span class="text-gray-400 text-xs">(<a href="{{ route('admin.health-checks.readme') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Readme</a>)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('phpserverlessproject_sponsors.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">PHP Serverless Project Sponsors</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-purple-400 font-bold">›</span>
                            <a href="{{ route('footer_links.index') }}"
                               class="text-blue-600 hover:underline hover:text-gray-900">Footer Links</a>
                        </div>
                    </div>
                </div>

            @endcan

        </div>

    </div>

</x-layouts.app>