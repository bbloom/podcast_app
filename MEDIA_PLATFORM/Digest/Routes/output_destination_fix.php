<?php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers\OutputDestinationSftpFixController;
use Illuminate\Support\Facades\Route;


// =========================================================================
// OUTPUT DESTINATION — Fix & Retry Routes
//
// These routes are entered exclusively from step 7 (SFTP connection test)
// when the AJAX test fails. Each route shows a focused form pre-filled from
// the session, saves the correction back to the session, and redirects
// straight back to step 7.
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