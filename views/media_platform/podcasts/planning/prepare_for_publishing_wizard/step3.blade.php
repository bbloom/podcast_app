<x-layouts.app title="Prepare For Publishing — Confirm">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="3" />

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Confirm Publishing</h1>
        <p class="text-sm text-gray-500">{{ $episode->formatted_title }}</p>
    </div>

    {{-- Summary of what will happen --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-gray-50 border-b border-gray-200 px-4 py-2">
            <span class="text-sm font-semibold text-gray-700">The following will happen immediately and cannot be undone:</span>
        </div>
        <ul class="divide-y divide-gray-100 text-sm">
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    A new record will be created in <code class="text-xs bg-gray-100 px-1 rounded">podcast_episodes_published</code>
                    for <strong>{{ $episode->formatted_title }}</strong>.
                </span>
            </li>
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    <strong>{{ $guestCount }} guest(s)</strong> will be migrated to the published episode.
                </span>
            </li>
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    <strong>{{ $linkCount }} link(s)</strong> will be migrated to the published episode.
                </span>
            </li>
            <li class="flex items-start gap-3 px-5 py-3 bg-red-50">
                <span class="text-red-600 font-bold mt-0.5">✕</span>
                <span class="text-red-700">
                    <strong>This planning record will be permanently deleted.</strong>
                    There is no undo. The episode will no longer exist in the planning table.
                </span>
            </li>
        </ul>
    </div>

    <div class="border border-red-200 rounded-lg p-4 bg-red-50 mb-8 text-sm text-red-700">
        <strong>This is the point of no return.</strong> Once confirmed, the episode moves to the Post-Production pipeline.
        If anything needs changing, go back now.
    </div>

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.publish.step3.store') }}">
        @csrf
        <div class="flex items-center justify-between">
            <a href="{{ route('podcast_episodes_planning.wizard.publish.step2') }}"
               class="text-sm text-gray-500 hover:underline">← Back</a>
            <button type="submit"
                    class="px-6 py-3 bg-red-600 text-white rounded hover:bg-red-700 font-semibold">
                Confirm — Publish Episode & Delete Planning Record
            </button>
        </div>
    </form>

</div>
</x-layouts.app>