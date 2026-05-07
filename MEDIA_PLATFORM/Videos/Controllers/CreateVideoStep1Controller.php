<?php

namespace MediaPlatform\Videos\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wizard Step 1 — collect title, description, and scheduled date.
 */
class CreateVideoStep1Controller extends Controller
{
    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    /**
     * Display the Step 1 form.
     */
    public function show(): View
    {
        return view('media_platform.videos.create_wizard.step1');
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    /**
     * Validate and store Step 1 data in the session, then redirect to Step 2.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:5000'],
            'scheduled_date' => ['required', 'date'],
        ]);

        session([
            'wizard.create_video.title'          => $validated['title'],
            'wizard.create_video.description'    => $validated['description'],
            'wizard.create_video.scheduled_date' => $validated['scheduled_date'] ?? null,
        ]);

        return redirect()->route('videos.create.step2');
    }
}