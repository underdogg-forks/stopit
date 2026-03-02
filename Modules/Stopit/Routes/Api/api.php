<?php

use Illuminate\Support\Facades\Route;
use Stopit\src\Providers\src\Http\Controllers\ExceptionController;
use Stopit\src\Providers\src\Http\Middleware\ApiTokenMiddleware;

Route::prefix('v1')->middleware(ApiTokenMiddleware::class)->group(function () {
    Route::post('/exceptions', [ExceptionController::class, 'store']);
});
