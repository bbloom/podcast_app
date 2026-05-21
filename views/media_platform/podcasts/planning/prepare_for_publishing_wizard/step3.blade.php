<x-layouts.app title="Prepare For Publishing — Confirm">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.prepare_for_publishing_wizard._step_dots :current="3" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Prepare For Publishing Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Step 3: Confirm Publishing</h1>
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


    {{-- Summary of what will happen --}}
    <div class="border-2 border-amber-400 bg-amber-100 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-2">
            <span class="text-lg font-semibold text-gray-700">The following will happen immediately and cannot be undone:</span>
        </div>

        <ul class="divide-y divide-gray-100 text-base">
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    A new record will be created in <code class="text-xs bg-gray-100 px-1 rounded">podcast_episodes_published</code>
                    for <strong>{{ $episode->formatted_title }}</strong>.
                </span>
            </li>

            <hr class="border-t-2 border-amber-200 my-1">

            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    <strong>{{ $guestCount }} guest(s)</strong> will be migrated to the published episode.
                </span>
            </li>

            <hr class="border-t-2 border-amber-200 my-1">

            <li class="flex items-start gap-3 px-5 py-3">
                <span class="text-green-600 font-bold mt-0.5">✓</span>
                <span class="text-gray-800">
                    <strong>{{ $linkCount }} link(s)</strong> will be migrated to the published episode.
                </span>
            </li>
        </ul>
    </div>

    <div class="border-2 border-red-300 rounded-lg p-4 bg-red-50 mb-8 text-base text-red-700">
        <strong>This planning record will be permanently deleted.</strong>
        <br>
        There is no undo. 
        <br>
        The episode will no longer exist in the planning table.
    </div>



    <div class="border-2 border-red-300 rounded-lg p-4 bg-red-50 mb-8 text-base text-red-700">
        <strong>This is the point of no return.</strong> 
        <br>
        Once confirmed, the episode moves to the Post-Production pipeline.
        <br>
        If anything needs changing, go back now.
    </div>

    <br>

    <form method="POST" action="{{ route('podcast_episodes_planning.wizard.publish.step3.store') }}">
        @csrf
        <div class="flex items-center justify-center">
            <button 
                type="submit"
                class="px-6 py-3 bg-red-700 text-white rounded-full font-semibold text-sm hover:bg-red-900"
            >
                Confirm — Create the Publishing Episode & Delete this Planning Record
            </button>
        </div>
    </form>

    <br><br>

    <form method="GET" action="{{ route('podcast_episodes_planning.wizard.publish.step2') }}">
        @csrf
        <div class="flex items-center justify-center">
            <button 
                type="submit"
                class="px-6 py-3 bg-green-700 text-white rounded-full font-semibold text-lg hover:bg-green-900"
            >
                ← Back to Step 2
            </button>
        </div>
    </form>


</div>
</x-layouts.app>