<x-layouts.app title="Dashboard">

    <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

    <p class="text-lg mb-8">
        Welcome, <strong>{{ auth()->user()->name }}</strong>!
    </p>

    {{-- Podcast Studio --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">Podcast Studio</h2>

        <div class="space-y-2 pl-4">

            {{-- Pre-Production --}}
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pre-Production </span>
            </div>
            <div class="pl-4 space-y-2 mb-3">
                <div>
                    <a href="{{ route('pre_production_create_podcast_episode.step1') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Create a podcast episode</a>
                </div>
            </div>

            {{-- Post Production --}}
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Post-Production</span>
            </div>
            <div class="pl-4 space-y-2 mb-3">
                <div>
                    <a href="{{ route('post_production.dashboard') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Post-Production Dashboard</a>
                </div>
            </div>
                
            {{-- Management --}}
            <div class="mb-1">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</span>
            </div>
            <div class="pl-4 space-y-2 mb-3">
                <div>
                    <a href="{{ route('podcast_shows.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Podcast Shows</a>
                </div>
                <div>
                    <a href="{{ route('podcast_episodes.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Podcast Episodes</a>
                </div>
                @can('admin')
                 <div>
                    <a href="{{ route('podcast_guests.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Podcast Guests</a>
                </div>
                @endcan
                <div>
                    <a href="{{ route('podcast_links.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Podcast Links</a>
                </div>
            </div>

        </div>
    </div>

    {{-- Digest --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">Digest</h2>
        <div class="space-y-2 pl-4">

            {{-- Lists and Destinations --}}
            <div class="mb-1">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lists and Destinations</span>
            </div>
            <div class="pl-4 space-y-2 mb-3">
                <div>
                    <a href="{{ route('lists.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">My Lists</a>
                </div>
                <div>
                    <a href="{{ route('output_destinations.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Output Destinations</a>
                </div>
            </div>

            {{-- Content Sources --}}
            <div class="mb-1">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Content Sources</span>
            </div>
            <div class="pl-4 space-y-2 mb-3"> 
                <div>
                    <a href="{{ route('youtube.channels.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Youtube Channels</a>
                </div>
                <div>
                    <a href="{{ route('podcasts.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Podcasts</a>
                </div>
                <div>
                    <a href="{{ route('text_based_rss_feeds.index') }}"
                       class="text-blue-600 hover:underline hover:text-gray-900">Text Based RSS Feeds</a>
                </div>
            </div>

            {{-- Processing & Publishing --}}
            @can('admin')
            <div class="mb-1 pt-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider" >Processing &amp; Publishing</span>
            </div>
            <div class="pl-4 space-y-2">
                <div>
                    <span class="text-gray-400">Processing <em class="text-xs">(future development)</em></span>
                </div>
                <div>
                    <span class="text-gray-400">Publishing <em class="text-xs">(future development)</em></span>
                </div>
            </div>
            @endcan

        </div>
    </div>

    {{-- PSN Content Manager --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">PSN Content Manager</h2>
        <div class="space-y-2 pl-4">
            <div>
                <span class="text-gray-400"><em class="text-xs">(future development)</em></span>
            </div>
        </div>
    </div>

    @can('admin')
    {{-- Configuration --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">Configuration</h2>
        <div class="space-y-2 pl-4">
            <div>
                <a href="{{ route('language_models.providers.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">Providers</a>
            </div>
            <div>
                <a href="{{ route('language_models.languagemodel.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">Language Models</a>
            </div>
            <div>
                <a href="{{ route('language_models.usecases.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">Use Cases</a>
            </div>
            <div>
                <span class="text-gray-400">Auphonic <em class="text-xs">(future development)</em></span>
            </div>
            <div>
                <span class="text-gray-400">AWS <em class="text-xs">(future development)</em></span>
            </div>
            <div>
                <span class="text-gray-400">Cloudflare <em class="text-xs">(future development)</em></span>
            </div>
        </div>
    </div>
    @endcan

    @can('admin')
    {{-- Tools --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">Tools</h2>
        <div class="space-y-2 pl-4">
            <div>
                <a href="/adhocprompt"
                   class="text-blue-600 hover:underline hover:text-gray-900">Ad Hoc Prompt</a>
            </div>
            <div>
                <a href="{{ route('admin.database-backups.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">Database Backups</a>
            </div>
            <div>
                <a href="/admin/health-checks"
                   class="text-blue-600 hover:underline hover:text-gray-900">Health Checks</a>
                &nbsp;(<a href="/admin/health-checks/readme"
                   class="text-blue-600 hover:underline hover:text-gray-900">Readme</a>)
            </div>
            <div>
                <a href="{{ route('phpserverlessproject_sponsors.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">PHP Serverless Project Sponsors</a>
            </div>            
        </div>
    </div>
    @endcan

    {{-- Account --}}
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider mb-3">Account</h2>
        <div class="space-y-2 pl-4">
            <div>
                <a href="/account/settings"
                   class="text-blue-600 hover:underline hover:text-gray-900">Account Settings</a>
            </div>
            <div>
                <a href="{{ route('profile.edit') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">Profile</a>
            </div>
        </div>
    </div>

</x-layouts.app>