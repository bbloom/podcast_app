<?php 

use MediaPlatform\Configuration\LanguageModels\Controllers\LanguageModelController;
use MediaPlatform\Configuration\LanguageModels\Controllers\LanguageModelUseCaseController;
use MediaPlatform\Configuration\Providers\Controllers\ProviderController;
use MediaPlatform\Configuration\UseCases\Controllers\UseCaseController;
use Illuminate\Support\Facades\Route;


// --------------------------------------------------------
// LanguageModelController
// --------------------------------------------------------

Route::get('language_models', [LanguageModelController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.index');

Route::get('language_models/create', [LanguageModelController::class, 'create'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.create');

Route::post('language_models', [LanguageModelController::class, 'store'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.store');

Route::get('language_models/{language_model}', [LanguageModelController::class, 'show'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.show');

Route::get('language_models/{language_model}/edit', [LanguageModelController::class, 'edit'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.edit');

Route::put('language_models/{language_model}', [LanguageModelController::class, 'update'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.update');

Route::delete('language_models/{language_model}', [LanguageModelController::class, 'destroy'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.destroy');


// --------------------------------------------------------
// LanguageModelUseCaseController — attach / detach
// --------------------------------------------------------

Route::post('language_models/{language_model}/use-cases', [LanguageModelUseCaseController::class, 'attach'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.use_cases.attach');

Route::delete('language_models/{language_model}/use-cases/{use_case}', [LanguageModelUseCaseController::class, 'detach'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.languagemodel.use_cases.detach');


// --------------------------------------------------------
// ProviderController
// --------------------------------------------------------

Route::get('providers', [ProviderController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.index');

Route::get('providers/create', [ProviderController::class, 'create'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.create');

Route::post('providers', [ProviderController::class, 'store'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.store');

Route::get('providers/{provider}', [ProviderController::class, 'show'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.show');

Route::get('providers/{provider}/edit', [ProviderController::class, 'edit'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.edit');

Route::put('providers/{provider}', [ProviderController::class, 'update'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.update');


// --------------------------------------------------------
// ProviderController (continued)
// --------------------------------------------------------

Route::delete('providers/{provider}', [ProviderController::class, 'destroy'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.providers.destroy');


// --------------------------------------------------------
// UseCaseController
// --------------------------------------------------------

Route::get('use-cases', [UseCaseController::class, 'index'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.index');

Route::get('use-cases/create', [UseCaseController::class, 'create'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.create');

Route::post('use-cases', [UseCaseController::class, 'store'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.store');

Route::get('use-cases/{use_case}', [UseCaseController::class, 'show'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.show');

Route::get('use-cases/{use_case}/edit', [UseCaseController::class, 'edit'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.edit');

Route::put('use-cases/{use_case}', [UseCaseController::class, 'update'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.update');

Route::delete('use-cases/{use_case}', [UseCaseController::class, 'destroy'])
    ->middleware(['auth', 'can:admin'])
    ->name('language_models.usecases.destroy');