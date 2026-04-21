<?php

// =============================================================================
// Routes: Footer Links
//
// CRUD routes for managing footer links on podcast show front-end websites.
// All routes require authentication.
//
// Loaded via routes/web.php.
// =============================================================================

use Illuminate\Support\Facades\Route;
use MediaPlatform\Tools\FooterLinks\Controllers\FooterLinkController;

Route::get('/footer-links', [FooterLinkController::class, 'index'])
    ->middleware(['auth'])
    ->name('footer_links.index');

Route::get('/footer-links/create', [FooterLinkController::class, 'create'])
    ->middleware(['auth'])
    ->name('footer_links.create');

Route::post('/footer-links', [FooterLinkController::class, 'store'])
    ->middleware(['auth'])
    ->name('footer_links.store');

Route::get('/footer-links/{footer_link}', [FooterLinkController::class, 'show'])
    ->middleware(['auth'])
    ->name('footer_links.show');

Route::get('/footer-links/{footer_link}/edit', [FooterLinkController::class, 'edit'])
    ->middleware(['auth'])
    ->name('footer_links.edit');

Route::put('/footer-links/{footer_link}', [FooterLinkController::class, 'update'])
    ->middleware(['auth'])
    ->name('footer_links.update');

Route::get('/footer-links/{footer_link}/delete', [FooterLinkController::class, 'deleteConfirm'])
    ->middleware(['auth'])
    ->name('footer_links.delete.confirm');

Route::delete('/footer-links/{footer_link}', [FooterLinkController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('footer_links.destroy');