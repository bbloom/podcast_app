{{--
    views/media_platform/database_backup/index.blade.php

    Admin view: database backup log table + "Run Now" button.

    Variables:
      $logs          (LengthAwarePaginator)  — paginated DatabaseBackupLog rows
      $backupResult  (array|null)            — step result from a manual run (flashed to session)
                                               Shape: ['overall_status', 'started_at',
                                                        'duration_seconds', 'steps']
--}}
<x-layouts.app title="Database Backups">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Database Backups</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Postgres dump → gzip → S3. Runs daily at 3:00 AM.
                </p>
            </div>

            {{-- Run Now button --}}
            <form method="POST" action="{{ route('admin.database-backups.run') }}"
                  onsubmit="return confirm('Run a database backup now? This will take up to 30 seconds.')">
                @csrf
                <button type="submit"
                        class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                    Run Backup Now
                </button>
            </form>
        </div>
    </div>

    {{-- ── Step-by-step result (shown after a manual "Run Now") ───────────── --}}
    @if ($backupResult)
        @php
            $success = $backupResult['overall_status'] === 'success';
        @endphp

        <div class="mb-8 border rounded-lg overflow-hidden {{ $success ? 'border-green-300' : 'border-red-300' }}">

            {{-- Result header --}}
            <div class="px-5 py-3 flex items-center justify-between
                        {{ $success ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex items-center gap-2">
                    @if ($success)
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm font-semibold text-green-800">Backup completed successfully</span>
                    @else
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm font-semibold text-red-800">Backup failed</span>
                    @endif
                </div>
                <span class="text-xs text-gray-500">
                    {{ $backupResult['started_at']->format('Y-m-d H:i:s') }}
                    · {{ $backupResult['duration_seconds'] }}s
                </span>
            </div>

            {{-- Step list --}}
            <div class="divide-y divide-gray-100">
                @foreach ($backupResult['steps'] as $step)
                    <div class="px-5 py-3 flex items-start gap-3">

                        {{-- Status icon --}}
                        @if ($step['status'] === 'success')
                            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @elseif ($step['status'] === 'failure')
                            <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        @else
                            {{-- skipped --}}
                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        @endif

                        <div>
                            <span class="text-sm font-semibold text-gray-700">{{ $step['label'] }}</span>
                            <span class="text-sm text-gray-500 ml-2">{{ $step['detail'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Log table ────────────────────────────────────────────────────────── --}}
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Backup Log</h2>

        @if ($logs->isEmpty())
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-5 py-4">
                <p class="text-sm text-gray-500">No backup runs recorded yet.</p>
            </div>
        @else
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ran At</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Filename</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Size</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-gray-50">

                                {{-- Status badge --}}
                                <td class="px-5 py-3">
                                    @if ($log->isSuccess())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">
                                            Success
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">
                                            Failed
                                        </span>
                                    @endif
                                </td>

                                {{-- Ran at --}}
                                <td class="px-5 py-3 text-gray-700 whitespace-nowrap">
                                    {{ $log->ran_at->format('Y-m-d H:i:s') }}
                                </td>

                                {{-- Filename --}}
                                <td class="px-5 py-3 text-gray-500 font-mono text-xs">
                                    {{ $log->filename ?? '—' }}
                                </td>

                                {{-- File size --}}
                                <td class="px-5 py-3 text-gray-700 whitespace-nowrap">
                                    {{ $log->humanFileSize() ?? '—' }}
                                </td>

                                {{-- Duration --}}
                                <td class="px-5 py-3 text-gray-700 whitespace-nowrap">
                                    {{ $log->duration_seconds !== null ? $log->duration_seconds . 's' : '—' }}
                                </td>

                                {{-- Message --}}
                                <td class="px-5 py-3 text-gray-500 text-xs max-w-xs truncate" title="{{ $log->message }}">
                                    {{ $log->message }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

</x-layouts.app>