<x-layouts.app title="Health Checks — Reference Guide">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-700 transition">← Dashboard</a>
        <span>›</span>
        <a href="{{ route('admin.health-checks.index') }}" class="hover:text-purple-700 transition">Health Checks</a>
        <span>›</span>
        <span class="text-gray-700">Reference Guide</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">Health Checks — Reference Guide</h1>

    <div class="space-y-6">

        {{-- ================================================================ --}}
        {{-- Three-Tier Error Response                                        --}}
        {{-- ================================================================ --}}
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                🔔 Three-Tier Error Response
            </h2>
            <div class="p-4 space-y-4">

                {{-- Tier 1 --}}
                <div class="border border-green-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-green-50 border-b border-green-200">
                        <span class="text-sm font-semibold text-green-800">Tier 1 — Self-Correcting</span>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-600 mb-2">Transient failures handled automatically. No email, no human action needed.</p>
                        <ul class="space-y-1">
                            @foreach ([
                                'API timeouts → retry with backoff (30s, 60s, 120s)',
                                'Rate limiting → job released back to queue with delay',
                                'Temporary network failures → retry up to 3 times',
                                'Single video transcript unavailable → skip it, continue',
                                'Duplicate processing → catch constraint violation, move on',
                            ] as $item)
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <span class="text-green-400 font-bold mt-0.5">›</span>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Tier 2 --}}
                <div class="border border-amber-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-amber-50 border-b border-amber-200">
                        <span class="text-sm font-semibold text-amber-800">Tier 2 — Degraded Mode</span>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-600 mb-2">System compensates automatically but sends you one email. Auto-resolves when the issue clears.</p>
                        <ul class="space-y-1">
                            @foreach ([
                                ['YouTube quota exhausted', 'YouTube processing paused; podcasts and RSS continue. Resets at midnight Pacific.'],
                                ['Transcript script broken', 'Falls back to description-only mode for YouTube sources.'],
                                ['Gemini unreachable', 'Stores raw content, marks as "needs summarisation." Next run picks up deferred items.'],
                                ['Channel returns 404', 'That list_source is auto-suspended; other sources continue.'],
                                ['5+ consecutive failures', 'Source auto-suspended to prevent waste.'],
                                ['SFTP destination unreachable', 'Digest publishing skipped for that destination.'],
                                ['Disk space low', 'Processing continues with a warning.'],
                                ['Failed jobs detected', 'One or more queued jobs failed permanently. Go to Health Checks and flush the failed jobs, or run php artisan queue:flush. Auto-resolves once the failed_jobs table is empty.'],
                            ] as [$title, $detail])
                                <li class="flex items-start gap-2 text-sm text-gray-600">
                                    <span class="text-amber-400 font-bold mt-0.5">›</span>
                                    <span><strong class="text-gray-800">{{ $title }}</strong> — {{ $detail }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Tier 3 --}}
                <div class="border border-red-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-red-50 border-b border-red-200">
                        <span class="text-sm font-semibold text-red-800">Tier 3 — Human Intervention Required</span>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-600 mb-3">Processing is <strong class="text-gray-800">blocked</strong> for the affected subsystem until you fix the issue and mark the alert as resolved.</p>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left font-semibold text-gray-700 px-3 py-2 w-1/3">Alert</th>
                                    <th class="text-left font-semibold text-gray-700 px-3 py-2">What to do</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ([
                                    ['Gemini API key invalid', 'Update GEMINI_API_KEY in .env, then restart the queue workers.'],
                                    ['Gemini model deprecated', 'Go to Language Models admin. Disable the old model, enable a replacement, and ensure the new model has the digest-processing use case.'],
                                    ['No model configured', 'Go to Language Models admin. Enable a model and attach the digest-processing use case to it.'],
                                    ['YouTube API key invalid', 'Update YOUTUBE_API_KEY in .env, then restart the queue workers.'],
                                    ['Python script missing', 'Restore scripts/get_transcript.py from version control and redeploy.'],
                                    ['Python dependencies missing', 'SSH into the server and run: pip install yt-dlp youtube-transcript-api'],
                                    ['Database unreachable', 'Check Postgres connection, credentials, and server status.'],
                                    ['Queue unreachable', 'Check the queue driver configuration and ensure Redis/database is running.'],
                                    ['Disk space critical', 'Free up space immediately — clean logs, temp files, or increase disk size.'],
                                    ['PHP upload_max_filesize too low', 'Update upload_max_filesize to at least 600M in your php.ini or FrankenPHP config and restart the server.'],
                                    ['PHP post_max_size too low', 'Update post_max_size to at least 600M in your php.ini or FrankenPHP config and restart the server.'],
                                    ['PHP memory_limit too low', 'Update memory_limit to at least 1G in your php.ini or FrankenPHP config and restart the server.'],
                                    ['PHP max_execution_time too low', 'Update max_execution_time to at least 300 in your php.ini or FrankenPHP config and restart the server.'],
                                ] as [$alert, $action])
                                    <tr class="border-t border-gray-100">
                                        <td class="px-3 py-2 text-gray-800 font-medium align-top">{{ $alert }}</td>
                                        <td class="px-3 py-2 text-gray-600 align-top">{{ $action }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- Resolution Workflow                                               --}}
        {{-- ================================================================ --}}
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                ✅ Resolution Workflow
            </h2>
            <div class="p-4">
                <ol class="space-y-2">
                    @foreach ([
                        'You receive an email about a Tier 3 alert.',
                        'You fix the underlying issue (e.g. update a key, enable a model).',
                        'You come to Health Checks and click "Mark Resolved" on the alert.',
                        'The Processing Gate unblocks the affected subsystem.',
                        'The next health check (every 15 minutes) confirms the fix.',
                        'If the fix didn\'t work, a new alert will be created.',
                    ] as $i => $step)
                        <li class="flex items-start gap-3 text-sm text-gray-600">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-purple-100 text-purple-700 text-xs font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                            {{ $step }}
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- Health Check Items                                                --}}
        {{-- ================================================================ --}}
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                🩺 Health Check Items
            </h2>
            <div class="p-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left font-semibold text-gray-700 px-3 py-2">Check</th>
                            <th class="text-left font-semibold text-gray-700 px-3 py-2">What it tests</th>
                            <th class="text-left font-semibold text-gray-700 px-3 py-2 whitespace-nowrap">Failure tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['Gemini API key',          'Sends a trivial prompt to the configured model',                   'Tier 3'],
                            ['Gemini model available',  'Queries Google\'s models list API to verify the model exists',     'Tier 3'],
                            ['YouTube API key',         'Makes a minimal API call (1 quota unit)',                          'Tier 3'],
                            ['Python script',           'Checks scripts/get_transcript.py exists',                          'Tier 3'],
                            ['Python dependencies',     'Imports yt-dlp and youtube-transcript-api',                        'Tier 3'],
                            ['Database',                'Runs SELECT 1',                                                     'Tier 3'],
                            ['Queue',                   'Verifies the queue driver is connected',                            'Tier 3'],
                            ['Failed jobs',             'Counts rows in the failed_jobs table',                              'Tier 2'],
                            ['Disk space',              'Checks free space on the server',                                   'Tier 2/3'],
                            ['PHP upload_max_filesize', 'Checks value is at least 500M',                                     'Tier 3'],
                            ['PHP post_max_size',       'Checks value is at least 500M',                                     'Tier 3'],
                            ['PHP memory_limit',        'Checks value is at least 1G (0 or -1 = unlimited = pass)',          'Tier 3'],
                            ['PHP max_execution_time',  'Checks value is at least 300s (0 = unlimited = pass)',              'Tier 3'],
                        ] as [$check, $what, $tier])
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2 text-gray-800 font-medium align-top whitespace-nowrap">{{ $check }}</td>
                                <td class="px-3 py-2 text-gray-600 align-top">{{ $what }}</td>
                                <td class="px-3 py-2 align-top whitespace-nowrap">
                                    @if ($tier === 'Tier 3')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Tier 3</span>
                                    @elseif ($tier === 'Tier 2')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">Tier 2</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">Tier 2</span><span class="mx-0.5 text-gray-400">/</span><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Tier 3</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- Processing Gate                                                   --}}
        {{-- ================================================================ --}}
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                🚦 Processing Gate
            </h2>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-3">Before any processing starts, the system checks for unresolved Tier 3 alerts. If a subsystem has a blocker, it is skipped — but other subsystems continue normally.</p>
                <ul class="space-y-1">
                    @foreach ([
                        ['YouTube blocked',    'youtube, gemini, infrastructure, queue'],
                        ['Podcasts blocked',   'podcast, gemini, infrastructure, queue'],
                        ['Text RSS blocked',   'text_based_rss, gemini, infrastructure, queue'],
                        ['Publishing blocked', 'sftp, infrastructure'],
                    ] as [$subsystem, $checks])
                        <li class="flex items-start gap-2 text-sm text-gray-600">
                            <span class="text-purple-400 font-bold mt-0.5">›</span>
                            <span><strong class="text-gray-800">{{ $subsystem }}</strong> — checks: <code class="text-purple-700 bg-purple-50 px-1 py-0.5 rounded text-xs">{{ $checks }}</code></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- Manual Health Check + Scheduling                                 --}}
        {{-- ================================================================ --}}
        <div class="border border-purple-300 rounded-lg overflow-hidden">
            <h2 class="text-sm font-semibold text-purple-700 uppercase tracking-wider px-4 py-3 border-b border-purple-300 bg-purple-50">
                ⚙️ Running &amp; Scheduling
            </h2>
            <div class="p-4 space-y-4">
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Manual health check</p>
                    <code class="text-purple-700 bg-purple-50 px-2 py-1 rounded text-sm">php artisan health:check</code>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Scheduler</p>
                    <ul class="space-y-1">
                        @foreach ([
                            'Health checks run every 15 minutes via the Laravel scheduler.',
                            'Due list processing is checked every 5 minutes.',
                            'Both require the standard Laravel cron entry on the server.',
                        ] as $item)
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <span class="text-purple-400 font-bold mt-0.5">›</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>

</x-layouts.app>