<x-layouts.app title="Create New Episode">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="1" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Create a New Episode</h1>
        <p class="text-gray-600">
            This wizard will create a new planning episode. You'll select a show,
            give the episode a title and number, and optionally add a scheduled date
            and initial theme notes.
        </p>
    </div>

    <div class="border border-purple-300 rounded-lg p-6 mb-8 bg-purple-50 text-sm text-gray-700 space-y-2">
        <p><span class="font-semibold text-purple-700">What gets created:</span> A planning record in the <code>podcast_episodes_planning</code> table.</p>
        <p><span class="font-semibold text-purple-700">Status set to:</span> New Episode Created.</p>
        <p><span class="font-semibold text-purple-700">What comes next:</span> Work on the theme, write the script, finalize it, and hand it off to publishing.</p>
    </div>

    <div class="text-center">
        <a href="{{ route('podcast_episodes_planning.wizard.create.step2') }}"
           class="inline-block px-8 py-3 bg-purple-700 text-white rounded-lg font-semibold hover:bg-purple-800 text-lg">
            Begin →
        </a>
    </div>

</div>
</x-layouts.app>