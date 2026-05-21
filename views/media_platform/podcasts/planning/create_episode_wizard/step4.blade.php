<x-layouts.app title="Episode Created!">
<div class="max-w-2xl mx-auto px-4 py-10">

    <x-podcasts.planning.create_episode_wizard._step_dots :current="4" />

    <div class="text-center mb-8">
        <div class="text-5xl mb-4">✓</div>
        <h1 class="text-3xl font-bold text-purple-800 mb-2">New Planning Episode Created!</h1>
        <p class="p-4 m-6 text-2xl text-purple-700 bg-green-300 border-2 border-purple-700 rounded-lg">
            <strong>{{ $episode->formatted_title }}</strong>
            <br><br>
            has been added to
            <br><br>
            <strong>{{ $episode->show->title }}</strong>.
        </p>
    </div>

    <br><br>

    <div class="border border-purple-300 rounded-lg overflow-hidden mb-8">
        <div class="bg-purple-50 border-b border-purple-300 px-4 py-2">
            <span class="text-base font-semibold text-purple-700 tracking-wider uppercase">What would you like to do next?</span>
        </div>
        <div class="divide-y divide-purple-300 bg-white text-base">
            <a href="{{ route('podcast_episodes_planning.wizard.create.step1') }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Create another new episode</span>
            </a>
            
            <a href="{{ route('podcast_episodes_planning.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Details for this episode</span>
            </a>

            <a href="{{ route('podcast_episodes_planning.theme.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Work on the theme</span>
            </a>

            <a href="{{ route('podcast_episodes_planning.script.show', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Work on the script</span>
            </a>

            <a href="{{ route('podcast_episodes_planning.guests.attach.index', $episode) }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Add guests</span>
            </a>

            <a href="{{ route('podcasts.dashboard') }}"
               class="flex items-center px-5 py-4 hover:bg-purple-100 group">
                <span class="text-purple-400 font-bold mr-3 group-hover:text-purple-700">›</span>
                <span class="text-lg text-gray-800 hover:text-purple-700 hover:underline">Go back to the main podcasting dashboard</span>
            </a>
        </div>
    </div>

</div>
</x-layouts.app>