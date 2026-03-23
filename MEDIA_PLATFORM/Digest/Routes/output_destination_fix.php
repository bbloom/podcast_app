<?php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers\OutputDestinationSftpFixController;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers\OutputDestinationWordPressFixController;
use Illuminate\Support\Facades\Route;


// =========================================================================
// OUTPUT DESTINATION — Fix & Retry Routes
//
// These routes are entered exclusively from the test step (step 7 for SFTP,
// wp3 for WordPress) when a connection test fails. Each route shows a focused
// form pre-filled from the session, saves the correction back to the session,
// and redirects straight back to the relevant test step.
//
// There is NO test_error_step session flag — the test step links directly to
// these routes when it receives an error_step value from the AJAX response.
// =========================================================================


// -------------------------------------------------------------------------
// SFTP fix routes
// -------------------------------------------------------------------------

Route::get('/output-destinations/fix/sftp/host', [OutputDestinationSftpFixController::class, 'host'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.host');

Route::post('/output-destinations/fix/sftp/host', [OutputDestinationSftpFixController::class, 'hostSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.host.submit');


Route::get('/output-destinations/fix/sftp/username', [OutputDestinationSftpFixController::class, 'username'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.username');

Route::post('/output-destinations/fix/sftp/username', [OutputDestinationSftpFixController::class, 'usernameSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.username.submit');


Route::get('/output-destinations/fix/sftp/auth', [OutputDestinationSftpFixController::class, 'auth'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.auth');

Route::post('/output-destinations/fix/sftp/auth', [OutputDestinationSftpFixController::class, 'authSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.auth.submit');


Route::get('/output-destinations/fix/sftp/path', [OutputDestinationSftpFixController::class, 'path'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.path');

Route::post('/output-destinations/fix/sftp/path', [OutputDestinationSftpFixController::class, 'pathSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.sftp.path.submit');


// -------------------------------------------------------------------------
// WordPress fix routes
// -------------------------------------------------------------------------

Route::get('/output-destinations/fix/wordpress/credentials', [OutputDestinationWordPressFixController::class, 'credentials'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.wordpress.credentials');

Route::post('/output-destinations/fix/wordpress/credentials', [OutputDestinationWordPressFixController::class, 'credentialsSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.wordpress.credentials.submit');


Route::get('/output-destinations/fix/wordpress/post-settings', [OutputDestinationWordPressFixController::class, 'postSettings'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.wordpress.post_settings');

Route::post('/output-destinations/fix/wordpress/post-settings', [OutputDestinationWordPressFixController::class, 'postSettingsSubmit'])
    ->middleware(['auth'])
    ->name('output_destinations.fix.wordpress.post_settings.submit');