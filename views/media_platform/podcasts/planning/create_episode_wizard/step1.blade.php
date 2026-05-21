<x-layouts.app title="Create New Episode">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="1" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Create a New Podcast Planning Episode</h1>
        <br>
        <p class="text-xl text-purple-700">
            This wizard will create a new podcast planning episode.
        </p>
        <br>
    </div>

    <div class="border-2 border-purple-300 rounded-lg p-6 mb-8 bg-purple-50 text-base text-gray-700 space-y-2">
        <p><span class="font-semibold text-purple-700">What gets created:</span>
        <br>A planning record in the "podcast_episodes_planning" table.</p>

        <br>
        <hr class="border-0 h-px bg-purple-400">
        <br>

        <p><span class="font-semibold text-purple-700">Sets the status to:</span>
        <br>New Episode Created.</p>
    </div>

    <br>
    <div class="text-center">
        <a href="{{ route('podcast_episodes_planning.wizard.create.step2') }}"
           class="inline-block px-8 py-3 bg-green-700 text-white rounded-lg font-semibold hover:bg-green-800 text-lg">
            Create new podcast planning episode →
        </a>
    </div>

</div>
</x-layouts.app>