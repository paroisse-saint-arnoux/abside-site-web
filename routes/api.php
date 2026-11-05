<?php

use Illuminate\Support\Facades\Route;

// API REST (jetons Passport) : les routes des événements arriveront avec le connecteur Claude.
Route::middleware(['auth:api', 'active', 'throttle:120,1'])->name('api.')->group(function () {
    //
});
