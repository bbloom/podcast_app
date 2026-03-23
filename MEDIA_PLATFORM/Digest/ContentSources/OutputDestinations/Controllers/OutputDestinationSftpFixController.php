<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * OutputDestinationSftpFixController — fix & retry forms for the SFTP wizard.
 *
 * Entered exclusively from step 7 (test connection) when the AJAX test fails.
 * Each method shows a focused form pre-filled from the session, saves the
 * correction back to the session, then redirects straight back to step 7.
 *
 * There is no test_error_step session flag — the fix route links are rendered
 * dynamically by the step 7 JS based on the error_step value in the AJAX response.
 *
 * ROUTES (defined in routes/output_destination_fix.php)
 *   GET/POST  /output-destinations/fix/sftp/host
 *   GET/POST  /output-destinations/fix/sftp/username
 *   GET/POST  /output-destinations/fix/sftp/auth
 *   GET/POST  /output-destinations/fix/sftp/path
 */

/*
 * Both controllers share the same pattern: guard → validate → save to session → reset test_passed to false → redirect back to the test step. 
 * The test_passed reset is important — it prevents someone saving a corrected form and then skipping the test by hitting the back button.
 * OutputDestinationSftpFixController::auth has the same $keyAlreadySaved / $passwordAlreadySaved logic as step5Submit — this is intentional
 * and correct. In the fix flow the user may already have a key saved from the initial setup, so blank submission should preserve it.
 * OutputDestinationWordPressFixController::credentialsSubmit uses the same blank-to-keep pattern for the app password, since it's sensitive 
 * and shouldn't need to be re-entered if only the URL or username changed.
*/

class OutputDestinationSftpFixController extends Controller
{
    /**
     * Guard: redirect to step 1 if the wizard session is missing or not SFTP.
     */
    private function guardSession(Request $request): bool
    {
        return $request->session()->get('od_wizard.type') === 'sftp'
            && $request->session()->has('od_wizard.host');
    }

    // =========================================================================
    // Host & port
    // =========================================================================

    /**
     * Show the host & port fix form, pre-filled from the session.
     */
    public function host(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-sftp.host');
    }

    /**
     * Save corrected host & port and return to the test step.
     */
    public function hostSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

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
        $request->session()->put('od_wizard.test_passed', false);

        return redirect()->route('output_destinations.create.step7');
    }

    // =========================================================================
    // Username
    // =========================================================================

    /**
     * Show the username fix form, pre-filled from the session.
     */
    public function username(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-sftp.username');
    }

    /**
     * Save corrected username and return to the test step.
     */
    public function usernameSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Please enter the SFTP username.',
        ]);

        $request->session()->put('od_wizard.username', $request->input('username'));
        $request->session()->put('od_wizard.test_passed', false);

        return redirect()->route('output_destinations.create.step7');
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    /**
     * Show the authentication fix form, pre-filled from the session.
     */
    public function auth(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-sftp.auth');
    }

    /**
     * Save corrected authentication details and return to the test step.
     */
    public function authSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

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

        $request->session()->put('od_wizard.test_passed', false);

        return redirect()->route('output_destinations.create.step7');
    }

    // =========================================================================
    // Path
    // =========================================================================

    /**
     * Show the path & base URL fix form, pre-filled from the session.
     */
    public function path(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-sftp.path');
    }

    /**
     * Save corrected path & base URL and return to the test step.
     */
    public function pathSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        $request->validate([
            'path'     => ['required', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
        ], [
            'path.required' => 'Please enter the remote path on the server.',
            'base_url.url'  => 'Please enter a valid URL, including https://.',
        ]);

        $request->session()->put('od_wizard.path',     $request->input('path'));
        $request->session()->put('od_wizard.base_url', $request->input('base_url'));
        $request->session()->put('od_wizard.test_passed', false);

        return redirect()->route('output_destinations.create.step7');
    }
}