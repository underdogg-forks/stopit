<?php

use Illuminate\Support\Facades\Route;
use Modules\Stopit\Http\Controllers\TenantSwitcherController;

Route::get('/', static function () {
    return view('welcome');
});

// Tenant switcher routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/switch-workspace', [TenantSwitcherController::class, 'index'])
        ->name('tenant.switcher');
    Route::get('/switch-workspace/{account}', [TenantSwitcherController::class, 'switch'])
        ->name('tenant.switch');
});
