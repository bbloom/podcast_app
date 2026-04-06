<x-layouts.app title="Health Checks — Reference Guide">

    <div class="mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.health-checks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition">
                ← Back to alerts
            </a>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mt-4">Health Checks — Reference Guide</h1>
    </div>

    <div class="prose prose-sm max-w-none
                prose-headings:text-gray-800 prose-headings:font-bold
                prose-h2:text-lg prose-h2:mt-8 prose-h2:mb-3 prose-h2:border-b prose-h2:border-gray-200 prose-h2:pb-2
                prose-h3:text-base prose-h3:mt-6 prose-h3:mb-2
                prose-p:text-gray-600 prose-p:leading-relaxed
                prose-li:text-gray-600
                prose-strong:text-gray-800
                prose-code:text-purple-700 prose-code:bg-purple-50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-xs
                prose-table:text-sm
                prose-th:text-left prose-th:font-semibold prose-th:text-gray-700 prose-th:bg-gray-50 prose-th:px-3 prose-th:py-2
                prose-td:px-3 prose-td:py-2 prose-td:text-gray-600 prose-td:border-t prose-td:border-gray-100">

        <h2>Three-Tier Error Response</h2>

        <h3>Tier 1 — Self-Correcting</h3>
        <p>Transient failures handled automatically. No email, no human action needed.</p>
        <ul>
            <li>API timeouts → retry with backoff (30s, 60s, 120s)</li>
            <li>Rate limiting → job released back to queue with delay</li>
            <li>Temporary network failures → retry up to 3 times</li>
            <li>Single video transcript unavailable → skip it, continue</li>
            <li>Duplicate processing → catch constraint violation, move on</li>
        </ul>

        <h3>Tier 2 — Degraded Mode</h3>
        <p>System compensates automatically but sends you one email. Auto-resolves when the issue clears.</p>
        <ul>
            <li><strong>YouTube quota exhausted</strong> — YouTube processing paused; podcasts and RSS continue. Resets at midnight Pacific.</li>
            <li><strong>Transcript script broken</strong> — falls back to description-only mode for YouTube sources.</li>
            <li><strong>Gemini unreachable</strong> — stores raw content, marks as "needs summarisation." Next run picks up deferred items.</li>
            <li><strong>Channel returns 404</strong> — that list_source is auto-suspended; other sources continue.</li>
            <li><strong>5+ consecutive failures</strong> — source auto-suspended to prevent waste.</li>
            <li><strong>SFTP destination unreachable</strong> — digest publishing skipped for that destination.</li>
            <li><strong>Disk space low</strong> — processing continues with a warning.</li>
        </ul>

        <h3>Tier 3 — Human Intervention Required</h3>
        <p>Processing is <strong>blocked</strong> for the affected subsystem until you fix the issue and mark the alert as resolved.</p>

        <table>
            <thead>
                <tr>
                    <th>Alert</th>
                    <th>What to do</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gemini API key invalid</td>
                    <td>Update <code>GEMINI_API_KEY</code> in <code>.env</code>, then restart the queue workers.</td>
                </tr>
                <tr>
                    <td>Gemini model deprecated</td>
                    <td>Go to Language Models admin. Disable the old model, enable a replacement, and ensure the new model has the <code>digest-processing</code> use case.</td>
                </tr>
                <tr>
                    <td>No model configured</td>
                    <td>Go to Language Models admin. Enable a model and attach the <code>digest-processing</code> use case to it.</td>
                </tr>
                <tr>
                    <td>YouTube API key invalid</td>
                    <td>Update <code>YOUTUBE_API_KEY</code> in <code>.env</code>, then restart the queue workers.</td>
                </tr>
                <tr>
                    <td>Python script missing</td>
                    <td>Restore <code>scripts/get_transcript.py</code> from version control and redeploy.</td>
                </tr>
                <tr>
                    <td>Python dependencies missing</td>
                    <td>SSH into the server and run: <code>pip install yt-dlp youtube-transcript-api</code></td>
                </tr>
                <tr>
                    <td>Database unreachable</td>
                    <td>Check Postgres connection, credentials, and server status.</td>
                </tr>
                <tr>
                    <td>Queue unreachable</td>
                    <td>Check the queue driver configuration and ensure Redis/database is running.</td>
                </tr>
                <tr>
                    <td>Disk space critical</td>
                    <td>Free up space immediately — clean logs, temp files, or increase disk size.</td>
                </tr>
                <tr>
                    <td>PHP upload_max_filesize too low</td>
                    <td>Update <code>upload_max_filesize</code> to at least <code>600M</code> in your <code>php.ini</code> or FrankenPHP config and restart the server.</td>
                </tr>
                <tr>
                    <td>PHP post_max_size too low</td>
                    <td>Update <code>post_max_size</code> to at least <code>600M</code> in your <code>php.ini</code> or FrankenPHP config and restart the server.</td>
                </tr>
                <tr>
                    <td>PHP memory_limit too low</td>
                    <td>Update <code>memory_limit</code> to at least <code>1G</code> in your <code>php.ini</code> or FrankenPHP config and restart the server.</td>
                </tr>
                <tr>
                    <td>PHP max_execution_time too low</td>
                    <td>Update <code>max_execution_time</code> to at least <code>300</code> in your <code>php.ini</code> or FrankenPHP config and restart the server.</td>
                </tr>
            </tbody>
        </table>

        <h2>Resolution Workflow</h2>
        <ol>
            <li>You receive an email about a Tier 3 alert.</li>
            <li>You fix the underlying issue (e.g. update a key, enable a model).</li>
            <li>You come here and click <strong>"Mark Resolved"</strong> on the alert.</li>
            <li>The Processing Gate unblocks the affected subsystem.</li>
            <li>The next health check (every 15 minutes) confirms the fix.</li>
            <li>If the fix didn't work, a new alert will be created.</li>
        </ol>

        <h2>Health Check Items</h2>

        <table>
            <thead>
                <tr>
                    <th>Check</th>
                    <th>What it tests</th>
                    <th>Failure tier</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Gemini API key</td><td>Sends a trivial prompt to the configured model</td><td>Tier 3</td></tr>
                <tr><td>Gemini model available</td><td>Queries Google's models list API to verify the model exists</td><td>Tier 3</td></tr>
                <tr><td>YouTube API key</td><td>Makes a minimal API call (1 quota unit)</td><td>Tier 3</td></tr>
                <tr><td>Python script</td><td>Checks <code>scripts/get_transcript.py</code> exists</td><td>Tier 3</td></tr>
                <tr><td>Python dependencies</td><td>Imports yt-dlp and youtube-transcript-api</td><td>Tier 3</td></tr>
                <tr><td>Database</td><td>Runs <code>SELECT 1</code></td><td>Tier 3</td></tr>
                <tr><td>Queue</td><td>Verifies the queue driver is connected</td><td>Tier 3</td></tr>
                <tr><td>Disk space</td><td>Checks free space on the server</td><td>Tier 2/3</td></tr>
                <tr><td>PHP upload_max_filesize</td><td>Checks value is at least 500M</td><td>Tier 3</td></tr>
                <tr><td>PHP post_max_size</td><td>Checks value is at least 500M</td><td>Tier 3</td></tr>
                <tr><td>PHP memory_limit</td><td>Checks value is at least 1G (0 or -1 = unlimited = pass)</td><td>Tier 3</td></tr>
                <tr><td>PHP max_execution_time</td><td>Checks value is at least 300s (0 = unlimited = pass)</td><td>Tier 3</td></tr>
            </tbody>
        </table>

        <h2>Processing Gate</h2>
        <p>Before any processing starts, the system checks for unresolved Tier 3 alerts. If a subsystem has a blocker, it is skipped — but other subsystems continue normally.</p>
        <ul>
            <li><strong>YouTube blocked</strong> — checks: youtube, gemini, infrastructure, queue</li>
            <li><strong>Podcasts blocked</strong> — checks: podcast, gemini, infrastructure, queue</li>
            <li><strong>Text RSS blocked</strong> — checks: text_based_rss, gemini, infrastructure, queue</li>
            <li><strong>Publishing blocked</strong> — checks: sftp, infrastructure</li>
        </ul>

        <h2>Manual Health Check</h2>
        <p>Run all checks from the terminal:</p>
        <p><code>php artisan health:check</code></p>

        <h2>Scheduling</h2>
        <ul>
            <li>Health checks run every <strong>15 minutes</strong> via the Laravel scheduler.</li>
            <li>Due list processing is checked every <strong>5 minutes</strong>.</li>
            <li>Both require the standard Laravel cron entry on the server.</li>
        </ul>

    </div>

</x-layouts.app>