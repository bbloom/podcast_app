<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * OutputDestinationWizardController — multi-step wizard for creating OutputDestinations.
 *
 * WIZARD FLOW
 * ───────────
 * Step 1 — Name (shared)
 * Step 2 — Type selection: 'sftp' or 'wordpress' (shared, then branches)
 *
 * SFTP path:
 *   Step 3 — Host & port
 *   Step 4 — Username
 *   Step 5 — Authentication (password or SSH key)
 *   Step 6 — Path & base URL
 *   Step 7 — Test connection (AJAX)
 *   Step 8 — Confirm & save
 *   Step 9 — Done
 *
 * WordPress path:
 *   WP1 — Site URL & credentials
 *   WP2 — Post settings
 *   WP3 — Test connection & confirm
 *   Step 9 — Done (shared)
 *
 * Fix & retry flows (connection test failures) are handled by separate controllers:
 *   OutputDestinationSftpFixController
 *   OutputDestinationWordPressFixController
 *
 * SESSION KEYS (common)
 *   od_wizard.name        — destination name (step 1)
 *   od_wizard.type        — 'sftp' or 'wordpress' (step 2)
 *   od_wizard.redirect_to — optional route name to return to after completion
 *
 * SESSION KEYS (SFTP)
 *   od_wizard.host, port, username, auth_type, password, private_key,
 *   passphrase, path, base_url, test_passed
 *
 * SESSION KEYS (WordPress)
 *   od_wizard.wordpress_url, wordpress_username, wordpress_app_password,
 *   wordpress_post_status, wordpress_category_ids, wordpress_tag_ids,
 *   wp_test_passed
 */
class OutputDestinationWizardController extends Controller
{
    public function __construct(
        private SftpService      $sftp,
        private WordPressService $wordpress,
    ) {}

    // =========================================================================
    // Index
    // =========================================================================

    /**
     * List all output destinations for the authenticated user.
     */
    public function index()
    {
        $destinations = OutputDestination::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.digest.content_sources.output_destinations.index', compact('destinations'));
    }

    // =========================================================================
    // Step 1 — Name (shared)
    // =========================================================================

    /**
     * Show the "name your destination" form.
     * Stores an optional redirect_to route name in the session for cross-wizard flows.
     */
    public function step1(Request $request)
    {
        if ($request->query('redirect_to')) {
            $request->session()->put('od_wizard.redirect_to', $request->query('redirect_to'));
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step1');
    }

    /**
     * Save the destination name and proceed to type selection.
     */
    public function step1Submit(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Please give this destination a name.',
        ]);

        $request->session()->put('od_wizard.name', $request->input('name'));

        return redirect()->route('output_destinations.create.step2');
    }

    // =========================================================================
    // Step 2 — Type selection (shared)
    // =========================================================================

    /**
     * Show the destination type selector (SFTP or WordPress).
     */
    public function step2(Request $request)
    {
        if (! $request->session()->has('od_wizard.name')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step2');
    }

    /**
     * Save the chosen type and branch to the correct next step.
     */
    public function step2Submit(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:sftp,wordpress'],
        ], [
            'type.required' => 'Please select a destination type.',
            'type.in'       => 'Please select a valid destination type.',
        ]);

        $request->session()->put('od_wizard.type', $request->input('type'));

        return match ($request->input('type')) {
            'wordpress' => redirect()->route('output_destinations.create.wp1'),
            default     => redirect()->route('output_destinations.create.step3'),
        };
    }

    // =========================================================================
    // Steps 3–9 — SFTP flow
    // =========================================================================

    /**
     * Step 3: SFTP host and port.
     */
    public function step3(Request $request)
    {
        if ($request->session()->get('od_wizard.type') !== 'sftp') {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step3');
    }

    /**
     * Save host and port, proceed to username.
     */
    public function step3Submit(Request $request)
    {
        $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
        ], [
            'host.required' => 'Please enter the server host.',
            'port.required' => 'Please enter the port number.',
            'port.min'      => 'Port must be between 1 and 65535.',
            'port.max'      => 'Port must be between 1 and 65535.',
        ]);

        $request->session()->put('od_wizard.host', $request->input('host'));
        $request->session()->put('od_wizard.port', (int) $request->input('port'));

        return redirect()->route('output_destinations.create.step4');
    }

    /**
     * Step 4: SFTP username.
     */
    public function step4(Request $request)
    {
        if (! $request->session()->has('od_wizard.host')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step4');
    }

    /**
     * Save the username and proceed to authentication.
     */
    public function step4Submit(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Please enter the SFTP username.',
        ]);

        $request->session()->put('od_wizard.username', $request->input('username'));

        return redirect()->route('output_destinations.create.step5');
    }

    /**
     * Step 5: SFTP authentication (password or SSH key).
     */
    public function step5(Request $request)
    {
        if (! $request->session()->has('od_wizard.username')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step5');
    }

    /**
     * Save the authentication details and proceed to path & base URL.
     */
    public function step5Submit(Request $request)
    {
        $keyAlreadySaved      = $request->session()->get('od_wizard.auth_type') === 'ssh_key'
                                && $request->session()->has('od_wizard.private_key');
        $passwordAlreadySaved = $request->session()->get('od_wizard.auth_type') === 'password'
                                && $request->session()->has('od_wizard.password');

        $request->validate([
            'auth_type'   => ['required', 'in:password,ssh_key'],
            'password'    => [$passwordAlreadySaved ? 'nullable' : 'required_if:auth_type,password', 'nullable', 'string'],
            'private_key' => [$keyAlreadySaved      ? 'nullable' : 'required_if:auth_type,ssh_key',  'nullable', 'string'],
            'passphrase'  => ['nullable', 'string'],
        ]);

        $request->session()->put('od_wizard.auth_type', $request->input('auth_type'));

        if ($request->filled('password')) {
            $request->session()->put('od_wizard.password', $request->input('password'));
        }

        if ($request->filled('private_key')) {
            $request->session()->put('od_wizard.private_key', trim($request->input('private_key')));
        }

        if ($request->filled('passphrase')) {
            $request->session()->put('od_wizard.passphrase', $request->input('passphrase'));
        }

        return redirect()->route('output_destinations.create.step6');
    }

    /**
     * Step 6: Remote path and public base URL.
     */
    public function step6(Request $request)
    {
        if (! $request->session()->has('od_wizard.auth_type')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step6');
    }

    /**
     * Save path and base URL, proceed to connection test.
     */
    public function step6Submit(Request $request)
    {
        $request->validate([
            'path'     => ['required', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
        ], [
            'path.required' => 'Please enter the remote path on the server.',
            'base_url.url'  => 'Please enter a valid URL, including https://.',
        ]);

        $request->session()->put('od_wizard.path',     $request->input('path'));
        $request->session()->put('od_wizard.base_url', $request->input('base_url'));

        return redirect()->route('output_destinations.create.step7');
    }

    /**
     * Step 7: Test the SFTP connection.
     * The user must pass the AJAX test before the "Next" button is enabled.
     */
    public function step7(Request $request)
    {
        if (! $request->session()->has('od_wizard.path')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step7');
    }

    /**
     * Proceed from the test step to confirm & save.
     * Guards against skipping a successful test.
     */
    public function step7Submit(Request $request)
    {
        if (! $request->session()->get('od_wizard.test_passed', false)) {
            return back()->withErrors(['test' => 'Please test your connection successfully before proceeding.']);
        }

        return redirect()->route('output_destinations.create.step8');
    }

    /**
     * AJAX endpoint: test the SFTP connection using session data.
     * Returns JSON with success, message, error_step, and debug fields.
     * On failure, error_step tells the JS which fix route to link to.
     */
    public function testConnection(Request $request)
    {
        $session  = $request->session();
        $authType = $session->get('od_wizard.auth_type');
        $host     = $session->get('od_wizard.host');
        $port     = $session->get('od_wizard.port', 22);
        $username = $session->get('od_wizard.username');
        $path     = $session->get('od_wizard.path', '/');

        $result = $authType === 'password'
            ? $this->sftp->testWithPassword($host, $port, $username, $session->get('od_wizard.password'), $path)
            : $this->sftp->testWithSshKey($host, $port, $username, $session->get('od_wizard.private_key'), $session->get('od_wizard.passphrase'), $path);

        $session->put('od_wizard.test_passed', $result['success']);

        $result['debug'] = [
            'host'      => $host,
            'port'      => $port,
            'username'  => $username,
            'path'      => $path,
            'auth_type' => $authType,
        ];

        return response()->json($result);
    }

    /**
     * Step 8: Confirm and save the SFTP destination.
     */
    public function step8(Request $request)
    {
        if (! $request->session()->get('od_wizard.test_passed', false)) {
            return redirect()->route('output_destinations.create.step7');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step8', [
            'data' => $request->session()->get('od_wizard'),
        ]);
    }

    /**
     * Save the SFTP destination and clear the wizard session.
     */
    public function step8Submit(Request $request)
    {
        if (! $request->session()->get('od_wizard.test_passed', false)) {
            return redirect()->route('output_destinations.create.step7');
        }

        $data = $request->session()->get('od_wizard');

        OutputDestination::create([
            'user_id'     => auth()->id(),
            'name'        => $data['name'],
            'type'        => 'sftp',
            'host'        => $data['host'],
            'port'        => $data['port'],
            'username'    => $data['username'],
            'auth_type'   => $data['auth_type'],
            'password'    => $data['auth_type'] === 'password' ? $data['password']    : null,
            'private_key' => $data['auth_type'] === 'ssh_key'  ? $data['private_key'] : null,
            'passphrase'  => $data['auth_type'] === 'ssh_key'  ? ($data['passphrase'] ?? null) : null,
            'path'        => $data['path'],
            'base_url'    => $data['base_url'] ?? null,
            'enabled'     => true,
        ]);

        $redirectTo = $data['redirect_to'] ?? null;
        $request->session()->forget('od_wizard');

        if ($redirectTo && Route::has($redirectTo)) {
            return redirect()->route($redirectTo)
                ->with('success', 'Output destination created. Now select it for your list.');
        }

        return redirect()->route('output_destinations.create.step9');
    }

    /**
     * Step 9: Done — shared completion page for both SFTP and WordPress.
     */
    public function step9()
    {
        return view('media_platform.digest.content_sources.output_destinations.wizard-step9');
    }

    // =========================================================================
    // WordPress wizard — WP1, WP2, WP3
    // =========================================================================

    /**
     * WP1: WordPress site URL and credentials.
     */
    public function wp1(Request $request)
    {
        if ($request->session()->get('od_wizard.type') !== 'wordpress') {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-wp1');
    }

    /**
     * Save the WordPress URL and credentials, proceed to post settings.
     */
    public function wp1Submit(Request $request)
    {
        $request->validate([
            'wordpress_url'          => ['required', 'url', 'max:500'],
            'wordpress_username'     => ['required', 'string', 'max:255'],
            'wordpress_app_password' => ['required', 'string', 'max:500'],
        ], [
            'wordpress_url.required'          => 'Please enter your WordPress site URL.',
            'wordpress_url.url'               => 'Please enter a valid URL, including https://.',
            'wordpress_username.required'     => 'Please enter your WordPress username.',
            'wordpress_app_password.required' => 'Please enter an Application Password.',
        ]);

        $request->session()->put('od_wizard.wordpress_url',          rtrim($request->input('wordpress_url'), '/'));
        $request->session()->put('od_wizard.wordpress_username',     $request->input('wordpress_username'));
        $request->session()->put('od_wizard.wordpress_app_password', $request->input('wordpress_app_password'));

        return redirect()->route('output_destinations.create.wp2');
    }

    /**
     * WP2: Post settings (status, categories, tags).
     */
    public function wp2(Request $request)
    {
        if (! $request->session()->has('od_wizard.wordpress_url')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-wp2');
    }

    /**
     * Save post settings and proceed to test & confirm.
     */
    public function wp2Submit(Request $request)
    {
        $request->validate([
            'wordpress_post_status'  => ['required', 'in:publish,draft,private'],
            'wordpress_category_ids' => ['nullable', 'string', 'max:500'],
            'wordpress_tag_ids'      => ['nullable', 'string', 'max:500'],
        ], [
            'wordpress_post_status.required' => 'Please select a post status.',
            'wordpress_post_status.in'       => 'Please select a valid post status.',
        ]);

        $request->session()->put('od_wizard.wordpress_post_status',  $request->input('wordpress_post_status', 'publish'));
        $request->session()->put('od_wizard.wordpress_category_ids', $request->input('wordpress_category_ids'));
        $request->session()->put('od_wizard.wordpress_tag_ids',      $request->input('wordpress_tag_ids'));

        return redirect()->route('output_destinations.create.wp3');
    }

    /**
     * WP3: Test connection and confirm before saving.
     */
    public function wp3(Request $request)
    {
        if (! $request->session()->has('od_wizard.wordpress_post_status')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-wp3', [
            'data' => $request->session()->get('od_wizard'),
        ]);
    }

    /**
     * AJAX endpoint: test the WordPress connection using session data.
     */
    public function testWordPressConnection(Request $request)
    {
        $session = $request->session();

        $result = $this->wordpress->testConnection(
            wordpressUrl: $session->get('od_wizard.wordpress_url'),
            username:     $session->get('od_wizard.wordpress_username'),
            appPassword:  $session->get('od_wizard.wordpress_app_password'),
        );

        $session->put('od_wizard.wp_test_passed', $result['success']);

        return response()->json($result);
    }

    /**
     * Save the WordPress destination and clear the wizard session.
     */
    public function wp3Submit(Request $request)
    {
        if (! $request->session()->get('od_wizard.wp_test_passed', false)) {
            return back()->withErrors(['test' => 'Please test your WordPress connection successfully before saving.']);
        }

        $data = $request->session()->get('od_wizard');

        OutputDestination::create([
            'user_id'                => auth()->id(),
            'name'                   => $data['name'],
            'type'                   => 'wordpress',
            'wordpress_url'          => $data['wordpress_url'],
            'wordpress_username'     => $data['wordpress_username'],
            'wordpress_app_password' => $data['wordpress_app_password'],
            'wordpress_post_status'  => $data['wordpress_post_status']  ?? 'publish',
            'wordpress_category_ids' => $data['wordpress_category_ids'] ?? null,
            'wordpress_tag_ids'      => $data['wordpress_tag_ids']      ?? null,
            'enabled'                => true,
        ]);

        $redirectTo = $data['redirect_to'] ?? null;
        $request->session()->forget('od_wizard');

        if ($redirectTo && Route::has($redirectTo)) {
            return redirect()->route($redirectTo)
                ->with('success', 'WordPress destination created. Now select it for your list.');
        }

        return redirect()->route('output_destinations.create.step9');
    }


    // =========================================================================
    // CRUD — Edit / Show / Update / Delete
    // =========================================================================


    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    /**
     * Show the edit form for an existing destination.
     */
    public function edit(OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        return view('media_platform.digest.content_sources.output_destinations.edit', compact('outputDestination'));
    }

    /**
     * Update an existing destination.
     * SFTP and WordPress fields are validated conditionally based on type.
     */
    public function update(Request $request, OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        $rules = [
            'name'    => ['required', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
        ];

        if ($outputDestination->type === 'sftp') {
            $rules += [
                'host'        => ['required', 'string', 'max:255'],
                'port'        => ['required', 'integer', 'min:1', 'max:65535'],
                'username'    => ['required', 'string', 'max:255'],
                'auth_type'   => ['required', 'in:password,ssh_key'],
                'password'    => ['nullable', 'string'],
                'private_key' => ['nullable', 'string'],
                'passphrase'  => ['nullable', 'string'],
                'path'        => ['required', 'string', 'max:500'],
                'base_url'    => ['nullable', 'url', 'max:500'],
            ];
        }

        if ($outputDestination->type === 'wordpress') {
            $rules += [
                'wordpress_url'          => ['required', 'url', 'max:500'],
                'wordpress_username'     => ['required', 'string', 'max:255'],
                'wordpress_app_password' => ['nullable', 'string', 'max:500'],
                'wordpress_post_status'  => ['required', 'in:publish,draft,private'],
                'wordpress_category_ids' => ['nullable', 'string', 'max:500'],
                'wordpress_tag_ids'      => ['nullable', 'string', 'max:500'],
            ];
        }

        $request->validate($rules);

        $outputDestination->update([
            'name'    => $request->input('name'),
            'enabled' => $request->boolean('enabled'),
        ]);

        if ($outputDestination->type === 'sftp') {
            $outputDestination->update([
                'host'        => $request->input('host'),
                'port'        => $request->input('port'),
                'username'    => $request->input('username'),
                'auth_type'   => $request->input('auth_type'),
                'password'    => $request->filled('password')    ? $request->input('password')         : $outputDestination->password,
                'private_key' => $request->filled('private_key') ? trim($request->input('private_key')) : $outputDestination->private_key,
                'passphrase'  => $request->filled('passphrase')  ? $request->input('passphrase')        : $outputDestination->passphrase,
                'path'        => $request->input('path'),
                'base_url'    => $request->input('base_url'),
            ]);
        }

        if ($outputDestination->type === 'wordpress') {
            $outputDestination->update([
                'wordpress_url'          => rtrim($request->input('wordpress_url'), '/'),
                'wordpress_username'     => $request->input('wordpress_username'),
                'wordpress_app_password' => $request->filled('wordpress_app_password')
                    ? $request->input('wordpress_app_password')
                    : $outputDestination->wordpress_app_password,
                'wordpress_post_status'  => $request->input('wordpress_post_status'),
                'wordpress_category_ids' => $request->input('wordpress_category_ids'),
                'wordpress_tag_ids'      => $request->input('wordpress_tag_ids'),
            ]);
        }

        return redirect()->route('output_destinations.index')
            ->with('success', 'Destination updated.');
    }


    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single output destination with its associated lists.
     */
    public function show(OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        $lists = $outputDestination->lists()
            ->paginate(config('admin.pagination_show'));

        return view('media_platform.digest.content_sources.output_destinations.show', compact('outputDestination', 'lists'));
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    /**
     * Show the delete confirmation page.
     */
    public function confirmDelete(OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        return view('media_platform.digest.content_sources.output_destinations.delete-confirm', compact('outputDestination'));
    }

    /**
     * Delete the destination. Blocked if any lists are currently using it.
     */
    public function destroy(OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        if ($outputDestination->lists()->exists()) {
            return redirect()->route('output_destinations.index')
                ->with('error', 'Cannot delete this destination — it is still being used by one or more lists. Remove or reassign those lists first.');
        }

        $outputDestination->delete();

        return redirect()->route('output_destinations.index')
            ->with('success', 'Destination deleted.');
    }
}