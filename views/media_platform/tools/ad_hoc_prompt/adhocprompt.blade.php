<x-layouts.app title="Ad Hoc Prompt">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-md p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-800">Ask Gemini</h1>

        <textarea
            id="prompt"
            rows="4"
            placeholder="Type your question..."
            class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
        ></textarea>

        <div>
            <label for="model-select" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Model</label>
            <select
                id="model-select"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                @foreach($models as $model)
                    <option value="{{ $model->slug }}">{{ $model->name }}</option>
                @endforeach
            </select>
        </div>
        

        <button
            id="submit-btn"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold mt-2 y-2 px-4 rounded-lg transition"
        >
            Send
        </button>


        <div id="answer-box" class="hidden">
            <div class="text-xs font-semibold text-gray-400 uppercase mb-1">Response</div>
            <div
                id="answer"
                class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap"
            ></div>
        </div>

        <div id="error-box" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3"></div>
    </div>

    <script>
        const btn        = document.getElementById('submit-btn');
        const promptEl   = document.getElementById('prompt');
        const modelEl    = document.getElementById('model-select');
        const answerBox  = document.getElementById('answer-box');
        const answerEl   = document.getElementById('answer');
        const errorBox   = document.getElementById('error-box');

        btn.addEventListener('click', async () => {
            const prompt = promptEl.value.trim();
            if (!prompt) return;

            btn.disabled = true;
            btn.textContent = 'Thinking…';
            answerBox.classList.add('hidden');
            errorBox.classList.add('hidden');

            try {
                const res = await fetch('{{ route("adhocprompt.prompt") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        model:  modelEl.value,
                    }),
                });

                if (!res.ok) throw new Error(`Server error: ${res.status}`);
                const data = await res.json();
                answerEl.textContent = data.answer;
                answerBox.classList.remove('hidden');
            } catch (err) {
                errorBox.textContent = err.message ?? 'Something went wrong.';
                errorBox.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Send';
            }
        });

        promptEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) btn.click();
        });
    </script>
</x-layouts.app>