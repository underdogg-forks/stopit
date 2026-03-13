<?php

use Illuminate\Support\Facades\Route;
use Modules\Stopit\Http\Controllers\ExceptionController;
use Modules\Stopit\Http\Middleware\ApiTokenMiddleware;

Route::prefix('v1')->middleware(ApiTokenMiddleware::class)->group(function () {
    Route::post('/exceptions', [ExceptionController::class, 'store']);
});
