<x-layouts.app title="Upload Recording — {{ $episode->title }}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-purple-700 tracking-wider">Upload Recording</h1>
        <a href="{{ route('post_production.upload_recording.index') }}"
           class="text-sm text-purple-700 hover:underline">
            &larr; Back to Upload Recording
        </a>
    </div>

    {{-- Flash error (from complete() redirect) --}}
    @session('error')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $value }}
        </div>
    @endsession

    {{-- Episode details --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Episode</div>
    <div class="border border-purple-500 rounded-lg pl-6 pr-4 py-4 mb-8">
        <div class="flex items-start gap-5">

            {{-- Show artwork --}}
            @if ($episode->show->itunes_image)
                <img src="{{ $episode->show->itunes_image }}"
                     alt="{{ $episode->show->title }}"
                     class="w-[75px] h-[75px] rounded-lg object-cover flex-shrink-0 mt-1">
            @endif

            {{-- Episode meta --}}
            <table class="text-base text-gray-600 border-collapse w-full">
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Show</td>
                    <td class="py-1 text-gray-800">{{ $episode->show->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode</td>
                    <td class="py-1 text-gray-800 font-medium">{{ $episode->title }}</td>
                </tr>
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Scheduled Date</td>
                    <td class="py-1 text-gray-800">{{ $episode->scheduled_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
                @if ($episode->raw_input_audio_filename)
                <tr>
                    <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Current Recording</td>
                    <td class="py-1 text-gray-800 font-mono text-sm">{{ $episode->raw_input_audio_filename }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- Upload section --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider">Upload WAV File</div>
    <div class="border border-purple-500 rounded-lg px-6 py-6 mb-8"
         x-data="uploadRecording('{{ route('post_production.upload_recording.store', $episode) }}', '{{ route('post_production.upload_recording.complete', $episode) }}')">

        {{-- File input --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="wav_file">
                Select WAV file
            </label>
            <input type="file"
                   id="wav_file"
                   accept=".wav"
                   class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                   @change="onFileSelected($event)"
                   :disabled="uploading || uploaded">
            <ul class="mt-3 ml-3 space-y-1 text-xs text-gray-400 list-disc list-outside pl-5">
                <li>Only WAV files are accepted.</li>
                <li>The file will be uploaded directly to S3 — large files may take several minutes.</li>
                <li>Do not close this page while the upload is in progress.</li>
            </ul>
        </div>

        {{-- Selected filename --}}
        <div x-show="filename" class="mb-4 text-sm text-gray-600">
            Selected: <span class="font-mono font-medium" x-text="filename"></span>
        </div>

        {{-- Upload button --}}
        <button type="button"
                @click="startUpload"
                :disabled="! file || uploading || uploaded"
                class="rounded bg-purple-700 px-5 py-2 text-sm font-semibold text-white hover:bg-purple-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="! uploading && ! uploaded">Upload to S3</span>
            <span x-show="uploading">Uploading…</span>
            <span x-show="uploaded">Uploaded ✓</span>
        </button>

        {{-- Progress bar --}}
        <div x-show="uploading" class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-300"
                     :style="'width: ' + progress + '%'"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500" x-text="progress + '% uploaded'"></p>
        </div>

        {{-- Inline error --}}
        <div x-show="errorMessage"
             class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"
             x-text="errorMessage">
        </div>

        {{-- Complete form — submitted by Alpine after S3 confirms the upload --}}
        <form id="complete-form"
              method="POST"
              action="{{ route('post_production.upload_recording.complete', $episode) }}"
              class="hidden">
            @csrf
        </form>

    </div>

@push('scripts')
<script>
function uploadRecording(presignUrl, completeUrl) {
    return {
        file:         null,
        filename:     '',
        uploading:    false,
        uploaded:     false,
        progress:     0,
        errorMessage: '',

        // ── File selected ──────────────────────────────────────────────────
        onFileSelected(event) {
            this.errorMessage = '';
            this.file         = event.target.files[0] ?? null;
            this.filename     = this.file ? this.file.name : '';
        },

        // ── Start the upload flow ──────────────────────────────────────────
        async startUpload() {
            if (! this.file) return;

            this.errorMessage = '';
            this.uploading    = true;
            this.progress     = 0;

            // Step 1 — request a pre-signed URL from the server.
            let presignedUrl;
            try {
                const response = await fetch(presignUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':           'application/json',
                    },
                    body: JSON.stringify({ filename: this.file.name }),
                });

                const data = await response.json();

                if (! response.ok) {
                    throw new Error(data.error ?? 'Could not generate upload URL. Please try again.');
                }

                presignedUrl = data.url;

            } catch (err) {
                this.errorMessage = err.message;
                this.uploading    = false;
                return;
            }

            // Step 2 — PUT the file directly to S3 using the pre-signed URL.
            // XMLHttpRequest is used here instead of fetch so we can track
            // upload progress via the xhr.upload.onprogress event.
            try {
                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();

                    xhr.upload.onprogress = (event) => {
                        if (event.lengthComputable) {
                            this.progress = Math.round((event.loaded / event.total) * 100);
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve();
                        } else {
                            reject(new Error('S3 upload failed (HTTP ' + xhr.status + '). Please try again.'));
                        }
                    };

                    xhr.onerror = () => reject(new Error('Network error during upload. Please check your connection and try again.'));

                    xhr.open('PUT', presignedUrl);
                    xhr.send(this.file);
                });

            } catch (err) {
                this.errorMessage = err.message;
                this.uploading    = false;
                return;
            }

            // Step 3 — notify the server that the upload is complete.
            // The server will verify the file exists in S3 and advance the status.
            this.progress = 100;
            this.uploaded = true;
            this.uploading = false;

            document.getElementById('complete-form').submit();
        },
    };
}
</script>
@endpush
</x-layouts.app>