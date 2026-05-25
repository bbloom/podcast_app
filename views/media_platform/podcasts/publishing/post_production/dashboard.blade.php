<x-layouts.app title="Post-Production">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <span class="text-gray-700">Post-Production</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Podcast Episode Post-Production</h1>

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- ================================================================ --}}
    {{-- IN PROGRESS                                                       --}}
    {{-- Episodes in intermediate pipeline statuses that need the user    --}}
    {{-- to return and continue. Shown only when at least one exists.     --}}
    {{-- ================================================================ --}}
    @if ($inProgressEpisodes->isNotEmpty())
        <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">In Progress</div>
        <div class="border border-purple-500 rounded-lg overflow-hidden mb-8">
            <div class="divide-y divide-gray-100">
                @foreach ($inProgressEpisodes as $episode)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate">
                                {{ $episode->title }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $episode->show->title }}
                                @if ($episode->scheduled_date)
                                    · {{ $episode->scheduled_date->format('M j, Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-4 ml-4 flex-shrink-0">
                            @php
                                $badgeClass = match ($episode->status) {
                                    \MediaPlatform\Podcasts\Publishing\Enums\PodcastEpisodeStatus::rss_validation_failed
                                        => 'bg-red-100 text-red-700',
                                    default
                                        => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                {{ $episode->status->label() }}
                            </span>
                            <a href="{{ route($episode->status->postProductionShowRoute(), $episode) }}"
                               class="text-sm font-semibold text-purple-700 hover:text-purple-900 hover:underline whitespace-nowrap">
                                Continue →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- PIPELINE                                                          --}}
    {{-- Steps listed in the new pipeline order (RSS Pipeline Reorder).   --}}
    {{-- Publish on Website now precedes Generate RSS Feed.               --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Pipeline</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8 space-y-4">

        {{-- 1. Upload Recording to S3 --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.upload_recording.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">
                    Upload Recording to S3
                </a>
            </div>
        </div>

        {{-- 2. Submit to Auphonic --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.auphonic_processing.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">
                    Submit to Auphonic
                </a>
            </div>
        </div>

        {{-- 3. Upload Production Audio File --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.upload_production_audio.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">
                    Upload the Production Audio File
                </a>
            </div>
        </div>

        {{-- 4. Publish on Website --}}
        {{-- (Moved before Generate RSS Feed — RSS Pipeline Reorder) --}}
        <div class="space-y-2 mb-3">
            <div>
                <a href="{{ route('post_production.publish_on_website.index') }}"
                   class="text-blue-600 hover:underline hover:text-gray-900">
                    Publish on Website
                </a>
            </div>
        </div>

        {{-- 5 & 6. Trigger Builds + Build Confirmation --}}
        {{-- These steps are reached automatically from Publish on Website. --}}
        {{-- If an episode is stuck in either step it appears in In Progress above. --}}
        <div class="space-y-2 mb-3">
            <div class="text-gray-400 text-sm">
                Trigger Static Site Build &amp; Build Confirmation
                <span class="ml-1 text-xs text-gray-300">(reached from Publish on Website)</span>
            </div>
        </div>

        {{-- 7. Generate RSS Feed --}}
        {{-- (Now follows Publish on Website — RSS Pipeline Reorder) --}}
        <div>
            <a href="{{ route('post_production.generate_rss_feed.index') }}"
               class="text-blue-600 hover:underline hover:text-gray-900">
                Generate RSS Feed File
            </a>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- MAINTENANCE                                                       --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Maintenance</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8 space-y-4">

        {{-- Regenerate RSS Feed --}}
        <div>
            <a href="{{ route('post_production.regenerate_rss_feed.index') }}"
               class="text-blue-600 hover:underline hover:text-gray-900">
                Regenerate RSS Feed
            </a>
            <p class="mt-1 text-xs text-gray-400">
                Rebuild and republish the RSS feed for any show — independent of the episode pipeline.
            </p>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- CONFIGURATION                                                     --}}
    {{-- ================================================================ --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Configuration</div>
    <div class="border border-purple-500 rounded-lg p-6 mb-8 space-y-4">

        {{-- Deploy Hooks --}}
        <div>
            <a href="{{ route('deploy_hooks.index') }}"
               class="text-blue-600 hover:underline hover:text-gray-900">
                Deploy Hooks
            </a>
            <p class="mt-1 text-xs text-gray-400">
                Manage deploy hook URLs for Cloudflare Pages, Netlify, and Vercel. Triggering a hook
                kicks off a fresh static site build for the associated show's front-end.
            </p>
        </div>

    </div>

</x-layouts.app>