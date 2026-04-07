<?php

use MediaPlatform\Digest\ContentSources\OutputDestinations\Controllers\OutputDestinationWizardController;
use MediaPlatform\Digest\ContentSources\Lists\Controllers\ListWizardController;
use Illuminate\Support\Facades\Route;


// =========================================================================
// OUTPUT DESTINATIONS
// =========================================================================

// -------------------------------------------------------------------------
// AJAX — connection test endpoint (placed first to avoid route conflicts)
// -------------------------------------------------------------------------

Route::post('/output-destinations/wizard/test-connection', [OutputDestinationWizardController::class, 'testConnection'])
    ->middleware(['auth'])
    ->name('output_destinations.wizard.test');


// -------------------------------------------------------------------------
// Output Destination Wizard — steps 1–9 (SFTP)
// -------------------------------------------------------------------------

Route::get('/output-destinations/create/step1', [OutputDestinationWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step1');

Route::post('/output-destinations/create/step1', [OutputDestinationWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step1.submit');


Route::get('/output-destinations/create/step2', [OutputDestinationWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step2');

Route::post('/output-destinations/create/step2', [OutputDestinationWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step2.submit');


Route::get('/output-destinations/create/step3', [OutputDestinationWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step3');

Route::post('/output-destinations/create/step3', [OutputDestinationWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step3.submit');


Route::get('/output-destinations/create/step4', [OutputDestinationWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step4');

Route::post('/output-destinations/create/step4', [OutputDestinationWizardController::class, 'step4Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step4.submit');


Route::get('/output-destinations/create/step5', [OutputDestinationWizardController::class, 'step5'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step5');

Route::post('/output-destinations/create/step5', [OutputDestinationWizardController::class, 'step5Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step5.submit');


Route::get('/output-destinations/create/step6', [OutputDestinationWizardController::class, 'step6'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step6');

Route::post('/output-destinations/create/step6', [OutputDestinationWizardController::class, 'step6Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step6.submit');


Route::get('/output-destinations/create/step7', [OutputDestinationWizardController::class, 'step7'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step7');

Route::post('/output-destinations/create/step7', [OutputDestinationWizardController::class, 'step7Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step7.submit');


Route::get('/output-destinations/create/step8', [OutputDestinationWizardController::class, 'step8'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step8');

Route::post('/output-destinations/create/step8', [OutputDestinationWizardController::class, 'step8Submit'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step8.submit');


Route::get('/output-destinations/create/step9', [OutputDestinationWizardController::class, 'step9'])
    ->middleware(['auth'])
    ->name('output_destinations.create.step9');


// -------------------------------------------------------------------------
// Output Destination CRUD
// -------------------------------------------------------------------------

Route::get('/output-destinations', [OutputDestinationWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('output_destinations.index');

Route::get('/output-destinations/{outputDestination}/edit', [OutputDestinationWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('output_destinations.edit');

Route::put('/output-destinations/{outputDestination}', [OutputDestinationWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('output_destinations.update');

Route::get('/output-destinations/{outputDestination}/delete', [OutputDestinationWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('output_destinations.delete.confirm');

Route::delete('/output-destinations/{outputDestination}', [OutputDestinationWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('output_destinations.destroy');

Route::get('/output-destinations/{outputDestination}', [OutputDestinationWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('output_destinations.show');


// =========================================================================
// LISTS
// =========================================================================

// -------------------------------------------------------------------------
// Lists Wizard
// -------------------------------------------------------------------------

Route::get('/lists/create/step1', [ListWizardController::class, 'step1'])
    ->middleware(['auth'])
    ->name('lists.create.step1');

Route::post('/lists/create/step1', [ListWizardController::class, 'step1Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step1.submit');


Route::get('/lists/create/step2', [ListWizardController::class, 'step2'])
    ->middleware(['auth'])
    ->name('lists.create.step2');

Route::post('/lists/create/step2', [ListWizardController::class, 'step2Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step2.submit');


Route::get('/lists/create/step3', [ListWizardController::class, 'step3'])
    ->middleware(['auth'])
    ->name('lists.create.step3');

Route::post('/lists/create/step3', [ListWizardController::class, 'step3Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step3.submit');


Route::get('/lists/create/step4', [ListWizardController::class, 'step4'])
    ->middleware(['auth'])
    ->name('lists.create.step4');

Route::post('/lists/create/step4', [ListWizardController::class, 'step4Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step4.submit');


Route::get('/lists/create/step5', [ListWizardController::class, 'step5'])
    ->middleware(['auth'])
    ->name('lists.create.step5');

Route::post('/lists/create/step5', [ListWizardController::class, 'step5Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step5.submit');


Route::get('/lists/create/step6', [ListWizardController::class, 'step6'])
    ->middleware(['auth'])
    ->name('lists.create.step6');

Route::post('/lists/create/step6', [ListWizardController::class, 'step6Submit'])
    ->middleware(['auth'])
    ->name('lists.create.step6.submit');


Route::get('/lists/create/step7', [ListWizardController::class, 'step7'])
    ->middleware(['auth'])
    ->name('lists.create.step7');


// -------------------------------------------------------------------------
// Lists CRUD
// -------------------------------------------------------------------------

Route::get('/lists', [ListWizardController::class, 'index'])
    ->middleware(['auth'])
    ->name('lists.index');

Route::get('/lists/{list}/edit', [ListWizardController::class, 'edit'])
    ->middleware(['auth'])
    ->name('lists.edit');

Route::put('/lists/{list}', [ListWizardController::class, 'update'])
    ->middleware(['auth'])
    ->name('lists.update');

Route::get('/lists/{list}/delete', [ListWizardController::class, 'confirmDelete'])
    ->middleware(['auth'])
    ->name('lists.delete.confirm');

Route::delete('/lists/{list}', [ListWizardController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('lists.destroy');

Route::get('/lists/{list}', [ListWizardController::class, 'show'])
    ->middleware(['auth'])
    ->name('lists.show');