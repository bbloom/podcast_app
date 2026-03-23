<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * OutputDestinationWordPressFixController — fix & retry forms for the WordPress wizard.
 *
 * Entered exclusively from wp3 (test connection) when the AJAX test fails.
 * Each method shows a focused form pre-filled from the session, saves the
 * correction back to the session, then redirects straight back to wp3.
 *
 * There is no test_error_step session flag — the fix route links are rendered
 * dynamically by the wp3 JS based on the error type in the AJAX response.
 *
 * ROUTES (defined in routes/output_destination_fix.php)
 *   GET/POST  /output-destinations/fix/wordpress/credentials
 *   GET/POST  /output-destinations/fix/wordpress/post-settings
 */

/*
 * Both controllers share the same pattern: guard → validate → save to session → reset test_passed to false → redirect back to the test step. 
 * The test_passed reset is important — it prevents someone saving a corrected form and then skipping the test by hitting the back button.
 * OutputDestinationSftpFixController::auth has the same $keyAlreadySaved / $passwordAlreadySaved logic as step5Submit — this is intentional
 * and correct. In the fix flow the user may already have a key saved from the initial setup, so blank submission should preserve it.
 * OutputDestinationWordPressFixController::credentialsSubmit uses the same blank-to-keep pattern for the app password, since it's sensitive 
 * and shouldn't need to be re-entered if only the URL or username changed.
*/

class OutputDestinationWordPressFixController extends Controller
{
    /**
     * Guard: redirect to step 1 if the wizard session is missing or not WordPress.
     */
    private function guardSession(Request $request): bool
    {
        return $request->session()->get('od_wizard.type') === 'wordpress'
            && $request->session()->has('od_wizard.wordpress_url');
    }

    // =========================================================================
    // Credentials (URL + username + application password)
    // =========================================================================

    /**
     * Show the credentials fix form, pre-filled from the session.
     */
    public function credentials(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-wordpress.credentials');
    }

    /**
     * Save corrected credentials and return to the WordPress test step.
     */
    public function credentialsSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        $appPasswordAlreadySaved = $request->session()->has('od_wizard.wordpress_app_password');

        $request->validate([
            'wordpress_url'          => ['required', 'url', 'max:500'],
            'wordpress_username'     => ['required', 'string', 'max:255'],
            'wordpress_app_password' => [$appPasswordAlreadySaved ? 'nullable' : 'required', 'nullable', 'string', 'max:500'],
        ], [
            'wordpress_url.required'          => 'Please enter your WordPress site URL.',
            'wordpress_url.url'               => 'Please enter a valid URL, including https://.',
            'wordpress_username.required'     => 'Please enter your WordPress username.',
            'wordpress_app_password.required' => 'Please enter an Application Password.',
        ]);

        $request->session()->put('od_wizard.wordpress_url',      rtrim($request->input('wordpress_url'), '/'));
        $request->session()->put('od_wizard.wordpress_username', $request->input('wordpress_username'));

        if ($request->filled('wordpress_app_password')) {
            $request->session()->put('od_wizard.wordpress_app_password', $request->input('wordpress_app_password'));
        }

        $request->session()->put('od_wizard.wp_test_passed', false);

        return redirect()->route('output_destinations.create.wp3');
    }

    // =========================================================================
    // Post settings (status, categories, tags)
    // =========================================================================

    /**
     * Show the post settings fix form, pre-filled from the session.
     */
    public function postSettings(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        return view('media_platform.digest.content_sources.output_destinations.fix-wordpress.post-settings');
    }

    /**
     * Save corrected post settings and return to the WordPress test step.
     */
    public function postSettingsSubmit(Request $request)
    {
        if (! $this->guardSession($request)) {
            return redirect()->route('output_destinations.create.step1');
        }

        $request->validate([
            'wordpress_post_status'  => ['required', 'in:publish,draft,private'],
            'wordpress_category_ids' => ['nullable', 'string', 'max:500'],
            'wordpress_tag_ids'      => ['nullable', 'string', 'max:500'],
        ], [
            'wordpress_post_status.required' => 'Please select a post status.',
            'wordpress_post_status.in'       => 'Please select a valid post status.',
        ]);

        $request->session()->put('od_wizard.wordpress_post_status',  $request->input('wordpress_post_status'));
        $request->session()->put('od_wizard.wordpress_category_ids', $request->input('wordpress_category_ids'));
        $request->session()->put('od_wizard.wordpress_tag_ids',      $request->input('wordpress_tag_ids'));

        $request->session()->put('od_wizard.wp_test_passed', false);

        return redirect()->route('output_destinations.create.wp3');
    }
}