<?php

use MediaPlatform\Tools\AdHocPrompt\Controllers\AdHocPromptController;

use Illuminate\Support\Facades\Route;



Route::get('/adhocprompt',  [AdHocPromptController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('adhocprompt.index')
;
Route::post('/adhocprompt', [AdHocPromptController::class, 'prompt'])
    ->middleware(['auth', 'can:admin'])
    ->name('adhocprompt.prompt')
;