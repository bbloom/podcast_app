<x-layouts.app title="Prepare For Publishing — Checklist">
<div class="max-w-2xl mx-auto px-4 py-10" x-data="{
    wavReady: false,
    websiteReady: false,
    get canProceed() { return this.wavReady && this.websiteReady; }
}">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="1" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Prepare For Publishing Wizard</h1>
        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 mt-4 shadow-sm">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                    alt="{{ $episode->show->title }}"
                    class="w-24 h-24 rounded object-cover border border-purple-200">
            @else
               {{ $episode->show->title ?? '' }} 
            @endif 
            {{ $episode->title }}
        </div>
    </div>

    {{-- ── This is it ───────────────────────────────────────────────────────── --}}
    <div class="border-2 border-amber-400 rounded-lg p-6 bg-amber-50 mb-6">
        <p class="text-xl font-bold text-amber-800 mb-3">
            This is the transition from Planning to Post-Production.
        </p>

        <hr class="border-t-2 border-amber-400 my-6">

        <p class="text-lg text-gray-800 mb-3">
            You are about to cross a one-way line.
        </p>
        
        <hr class="border-t-2 border-amber-400 my-6">

        <p class="text-lg text-gray-800 mb-3">
            When you complete this wizard:
            <ul class="list-disc list-inside space-y-2 text-base text-gray-800 mb-3">
                <li>this episode leaves the planning world <strong>permanently</strong></li>
                <li>this episode enters the post-production pipeline</li>
            </ul>
       </p>
       
       <hr class="border-t-2 border-amber-400 my-6">

        <p class="text-lg text-gray-800 mb-3">
            At this point, it is assumed that:
        </p>
        <ul class="list-disc list-inside space-y-2 text-base text-gray-800 mb-3">
            <li>The episode has been <strong>recorded</strong>.</li>
            <li>The raw audio has been <strong>edited and is ready for Auphonic processing</strong>.</li>
            <li>The WAV file is <strong>in the correct S3 location</strong>.</li>
            <li>The script, theme, website content, and episode details are <strong>final</strong>.</li>
            <li>Guests and links are <strong>attached and correct</strong>.</li>
        </ul>

        <hr class="border-t-2 border-amber-400 my-6">

        <p class="text-xl font-bold text-amber-800">
            There is no going back. 
            <br>
            This planning record will be permanently deleted.
        </p>
    </div>


    {{-- ── What this wizard will do ────────────────────────────────────────── --}}
    <div class="border-2 border-purple-300 rounded-lg p-6 bg-purple-50 text-lg text-gray-700 mb-6">
        <p class="font-semibold text-purple-700 mb-2">This wizard will:</p>
        <ol class="list-decimal list-inside space-y-4">
            <li>&nbsp;Review and confirm the episode details</li>
            <li>&nbsp;Create a record in <code>podcast_episodes_published</code></li>
            <li>&nbsp;Migrate {{ $episode->guests->count() }} guest(s) to the published episode</li>
            <li>&nbsp;Migrate {{ $episode->links->count() }} link(s) to the published episode</li>
            <li>&nbsp;<strong class="text-red-600">Permanently delete this planning record</strong></li>
        </ol>
    </div>


    {{-- ── Checklist ───────────────────────────────────────────────────────── --}}
    <div class="border-2 border-purple-300 rounded-lg p-6 mb-8 space-y-4">
        <p class="text-base font-semibold text-purple-700 mb-3">Before proceeding, confirm both:</p>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" x-model="wavReady" class="mt-0.5 accent-purple-700 w-4 h-4 shrink-0">
            <span class="text-base text-gray-800">
                <strong>WAV file is ready and accessible.</strong>
                The recorded and edited audio file is in the correct S3 location and ready for Auphonic.
            </span>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" x-model="websiteReady" class="mt-0.5 accent-purple-700 w-4 h-4 shrink-0">
            <span class="text-base text-gray-800">
                <strong>Website fields are complete.</strong>
                The website content and excerpt are written and ready to publish.
            </span>
        </label>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
           class="px-4 py-2 text-sm font-semibold border border-gray-400 text-gray-700 rounded hover:bg-gray-50">
            ← Back to Episode
        </a>

        <a href="{{ route('podcast_episodes_planning.wizard.publish.step2') }}"
           :class="canProceed ? 'bg-green-700 hover:bg-green-800 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'"
           class="px-6 py-2 rounded font-semibold text-sm transition">
            Continue →
        </a>
    </div>

</div>
</x-layouts.app>