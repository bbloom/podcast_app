<?php

namespace MediaPlatform\Podcasts\Planning\CreateEpisodeWizard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class Step1Controller extends Controller
{
    /**
     * Render the Create Episode Wizard introduction page.
     * No session manipulation — the wizard begins at Step 2.
     */
    public function show(): View
    {
        return view('media_platform.podcasts.planning.create_episode_wizard.step1');
    }
}