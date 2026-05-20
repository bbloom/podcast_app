<x-layouts.app title="Finalize Script — Introduction">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="1" />

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-3">Finalize the Script</h1>
        <p class="text-sm text-gray-500 mb-1">{{ $episode->show->title ?? '' }} &mdash; {{ $episode->formatted_title }}</p>
    </div>

    <div class="border border-purple-300 rounded-lg p-6 bg-purple-50 text-sm text-gray-700 space-y-2 mb-8">
        <p>This wizard will walk you through finalizing the script for recording:</p>
        <ol class="list-decimal list-inside space-y-1 mt-2">
            <li>Confirm the episode number</li>
            <li>Confirm the episode title</li>
            <li>Proof the script with AI tools</li>
            <li>Prepend the show intro</li>
            <li>Append the show outro</li>
            <li>Final review — script locked for recording</li>
        </ol>
        <p class="mt-3 font-semibold text-purple-700">When you're done, the status will be set to "Ready To Record."</p>
    </div>

    @session('info')
        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded text-sm">{{ $value }}</div>
    @endsession

    <div class="text-center">
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step2') }}"
           class="inline-block px-8 py-3 bg-purple-700 text-white rounded-lg font-semibold hover:bg-purple-800 text-lg">
            Begin →
        </a>
    </div>

</div>
</x-layouts.app>