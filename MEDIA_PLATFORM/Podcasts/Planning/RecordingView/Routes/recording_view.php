<?php

// =============================================================================
// Route: Planning Episode — Recording View
//
// Read-only view of the assembled script, guests, and links for an episode
// at ready_to_record status.
//
// Path: MEDIA_PLATFORM/Podcasts/Planning/RecordingView/Routes/
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Podcasts\Planning\RecordingView\Controllers\RecordingViewController;

Route::get('/podcasts/planning/{podcast_episode_planning}/recording', [RecordingViewController::class, 'show'])
    ->middleware(['auth'])
    ->name('podcast_episodes_planning.recording.show');