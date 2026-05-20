<x-layouts.app title="Prepare For Publishing — Checklist">
<div class="max-w-2xl mx-auto px-4 py-10" x-data="{
    wavReady: false,
    websiteReady: false,
    get canProceed() { return this.wavReady && this.websiteReady; }
}">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="1" />

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Prepare For Publishing</h1>
        <p class="text-sm text-gray-500">
            {{ $episode->show->title ?? '' }} &mdash; {{ $episode->formatted_title }}
        </p>
    </div>

    <div class="border border-purple-300 rounded-lg p-6 bg-purple-50 text-sm text-gray-700 mb-6">
        <p class="font-semibold text-purple-700 mb-2">This wizard will:</p>
        <ol class="list-decimal list-inside space-y-1">
            <li>Review and confirm the episode details</li>
            <li>Create a record in <code>podcast_episodes_published</code></li>
            <li>Migrate {{ $episode->guests->count() }} guest(s) to the published episode</li>
            <li>Migrate {{ $episode->links->count() }} link(s) to the published episode</li>
            <li><strong class="text-red-600">Permanently delete this planning record</strong></li>
        </ol>
    </div>

    {{-- Checklist --}}
    <div class="border border-gray-200 rounded-lg p-6 mb-8 space-y-4">
        <p class="text-sm font-semibold text-gray-700 mb-3">Before proceeding, confirm both items:</p>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" x-model="wavReady" class="mt-0.5 accent-purple-700 w-4 h-4 shrink-0">
            <span class="text-sm text-gray-800">
                <strong>WAV file is ready and accessible.</strong>
                The recorded and edited audio file is in the correct S3 location and ready for Auphonic.
            </span>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" x-model="websiteReady" class="mt-0.5 accent-purple-700 w-4 h-4 shrink-0">
            <span class="text-sm text-gray-800">
                <strong>Website fields are complete.</strong>
                The website content and excerpt are written and ready to publish.
            </span>
        </label>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
           class="text-sm text-gray-500 hover:underline">← Back to episode</a>

        <a href="{{ route('podcast_episodes_planning.wizard.publish.step2') }}"
           :class="canProceed ? 'bg-purple-700 hover:bg-purple-800 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'"
           class="px-6 py-2 rounded font-semibold text-sm transition">
            Continue →
        </a>
    </div>

</div>
</x-layouts.app>