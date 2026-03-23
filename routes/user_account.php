<?php

use Illuminate\Support\Facades\Route;


Route::get('/account/settings', function () {
    return view('account.settings');
})->middleware(['auth']);
