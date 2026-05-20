<x-layouts.app title="Episode Created!">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="4" />

    <div class="text-center mb-8">
        <div class="text-5xl mb-4">✓</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Episode Created!</h1>
        <p class="text-gray-600">
            <strong>{{ $episode->formatted_title }}</strong>
            has been added to <strong>{{ $episode->show->title }}</strong>.
        </p>
    </div>

    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-sm font-semibold text-purple-700 tracking-wider uppercase">What would you like to do next?</span>
        </div>
        <div class="divide-y divide-gray-100">
            <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
               class="flex items-center px-5 py-4 hover:bg-gray-50 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-gray-800">Create another new episode</span>
            </a>
            <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-gray-50 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-gray-800">View this episode</span>
            </a>
            <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-gray-50 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-gray-800">Work on the theme</span>
            </a>
            <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-gray-50 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-gray-800">Work on the script</span>
            </a>
        </div>
    </div>

</div>
</x-layouts.app>