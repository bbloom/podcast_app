<x-layouts.app title="Finalize Script — Confirm Title">
<div class="max-w-xl mx-auto px-4 py-10">

    <x-podcasts.planning.finalize_script_wizard._step_dots :current="3" />

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Finalize the Script Wizard</h1>
        <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">Step 3: Confirm the Title</h1>

        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-3xl font-bold text-purple-700 bg-sky-100 border-2 border-sky-700 rounded-lg px-6 py-4 mb-8 mt-4 shadow-sm">
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                    alt="{{ $episode->show->title }}"
                    class="w-24 h-24 rounded object-cover border border-purple-200">
            @else
               {{ $episode->show->title ?? '' }} 
            @endif 
            episode #{{ $episode->episode_number }}
            <span class="mt-4">{{ $episode->title }}</span>
        </div>

        @error('title')
            <div class="border-2 border-red-400 rounded-lg p-6 bg-red-50 mb-6 text-xl font-bold text-red-800">
                    @error('title') <p class="mt-1 text-lg text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="border-2 border-amber-400 rounded-lg p-6 bg-amber-50 mb-6 mt-6 text-xl font-bold text-amber-800">
                The final title is derived 
        </div>

        <div class="mb-3 border-2 border-amber-400 rounded-lg p-5 bg-amber-50">
            <p class="text-xl font-bold text-amber-800 mb-2">
                This is one of the most important decisions in production.
            </p>
            <p class="text-lg text-gray-800 mb-3">
                Once a listener has found your show, 
                <br>
                <strong>the title is the single biggest factor in whether they choose to listen.</strong> <br>
                <br>
                Take your time here. Make it count.
            </p>
            
            <br>
            <div class="text-left">
                <ul class="list-disc list-outside pl-5 space-y-1 text-base text-gray-800">
                    <li>Enter the <strong>title only</strong> — no episode number.</li>
                    <li>The episode number is added automatically on publishing.</li>
                </ul>
            </div>
        </div>


        <br>
        <form method="POST" action="{{ route('podcast_episodes_planning.wizard.finalize.step3.store') }}">
            @csrf

            <div class="border-2 border-purple-300 rounded-lg p-6 bg-purple-100 text-base text-gray-700 space-y-4 mb-8">
                <label for="title" class="block text-lg font-semibold text-gray-700 mb-1">Title</label>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $episode->title) }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-base focus:ring-2 focus:ring-purple-500 focus:outline-none @error('title') border-red-400 @enderror">
                
            </div>

            <br>
            <div class="flex items-center justify-between">
                <a href="{{ route('podcast_episodes_planning.wizard.finalize.step2') }}"
                class="text-sm text-gray-500 hover:underline">← Back</a>
                <button type="submit"
                        class="px-6 py-2 bg-green-700 text-white rounded font-semibold text-sm hover:bg-green-800">
                    Confirm →
                </button>
            </div>
        </form>
    </div>
</div>
</x-layouts.app>