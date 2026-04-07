<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * OutputDestinationWizardController — multi-step wizard for creating OutputDestinations.
 *
 * WIZARD FLOW
 * ───────────
 * Step 1 — Name
 * Step 2 — Type selection (currently only 'sftp')
 * Step 3 — Host & port
 * Step 4 — Username
 * Step 5 — Authentication (password or SSH key)
 * Step 6 — Path & base URL
 * Step 7 — Test connection (AJAX)
 * Step 8 — Confirm & save
 * Step 9 — Done
 *
 * Fix & retry flows (connection test failures) are handled by:
 *   OutputDestinationSftpFixController
 *
 * SESSION KEYS
 *   od_wizard.name        — destination name (step 1)
 *   od_wizard.type        — 'sftp' (step 2)
 *   od_wizard.redirect_to — optional route name to return to after completion
 *   od_wizard.host, port, username, auth_type, password, private_key,
 *   passphrase, path, base_url, test_passed
 */
class OutputDestinationWizardController extends Controller
{
    public function __construct(
        private SftpService $sftp,
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
    // Step 1 — Name
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
    // Step 2 — Type selection
    // =========================================================================

    /**
     * Show the destination type selector.
     */
    public function step2(Request $request)
    {
        if (! $request->session()->has('od_wizard.name')) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.wizard-step2');
    }

    /**
     * Save the chosen type and proceed to SFTP setup.
     */
    public function step2Submit(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:sftp'],
        ], [
            'type.required' => 'Please select a destination type.',
            'type.in'       => 'Please select a valid destination type.',
        ]);

        $request->session()->put('od_wizard.type', $request->input('type'));

        return redirect()->route('output_destinations.create.step3');
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
     * Step 9: Done — completion page.
     */
    public function step9()
    {
        return view('media_platform.digest.content_sources.output_destinations.wizard-step9');
    }

    // =========================================================================
    // CRUD — Edit / Show / Update / Delete
    // =========================================================================

    /**
     * Show the edit form for an existing destination.
     */
    public function edit(OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        return view('media_platform.digest.content_sources.output_destinations.edit', compact('outputDestination'));
    }

    /**
     * Update an existing SFTP destination.
     */
    public function update(Request $request, OutputDestination $outputDestination)
    {
        abort_if($outputDestination->user_id !== auth()->id(), 403);

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'enabled'     => ['nullable', 'boolean'],
            'host'        => ['required', 'string', 'max:255'],
            'port'        => ['required', 'integer', 'min:1', 'max:65535'],
            'username'    => ['required', 'string', 'max:255'],
            'auth_type'   => ['required', 'in:password,ssh_key'],
            'password'    => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'passphrase'  => ['nullable', 'string'],
            'path'        => ['required', 'string', 'max:500'],
            'base_url'    => ['nullable', 'url', 'max:500'],
        ]);

        $outputDestination->update([
            'name'        => $request->input('name'),
            'enabled'     => $request->boolean('enabled'),
            'host'        => $request->input('host'),
            'port'        => $request->input('port'),
            'username'    => $request->input('username'),
            'auth_type'   => $request->input('auth_type'),
            'password'    => $request->filled('password')    ? $request->input('password')          : $outputDestination->password,
            'private_key' => $request->filled('private_key') ? trim($request->input('private_key'))  : $outputDestination->private_key,
            'passphrase'  => $request->filled('passphrase')  ? $request->input('passphrase')         : $outputDestination->passphrase,
            'path'        => $request->input('path'),
            'base_url'    => $request->input('base_url'),
        ]);

        return redirect()->route('output_destinations.index')
            ->with('success', 'Destination updated.');
    }

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