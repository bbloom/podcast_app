<x-layouts.app title="Finalize Script — Introduction">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="1" />

     <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
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

    <br>
    <div class="border-2 border-purple-300 rounded-lg p-6 bg-purple-100 text-base text-gray-700 space-y-4 mb-8">
        <p>This wizard will walk you through finalizing the script for recording:</p>
        <ol class="list-decimal list-inside space-y-1 mt-2">
            <li>Confirm the episode number</li>
            <li>Confirm the episode title</li>
            <li>Proof the script with AI tools</li>
            <li>Review / update the show intro template</li>
            <li>Prepend the show intro to the script</li>
            <li>Review / update the show outro template</li>
            <li>Append the show outro to the script</li>
            <li>Final confirmation — status set to Ready to Record</li>
        </ol>
        <p class="mt-6 font-bold text-purple-700">At the end of this wizard, the status will be set to "Ready To Record."</p>
    </div>

    @session('info')
        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded text-base">{{ $value }}</div>
    @endsession

    <br>
    <div class="text-center">
        <a href="{{ route('podcast_episodes_planning.wizard.finalize.step2') }}"
           class="inline-block px-8 py-3 bg-green-700 text-white rounded-lg font-semibold hover:bg-green-800 text-lg">
            Begin →
        </a>
    </div>

</div>
</x-layouts.app>