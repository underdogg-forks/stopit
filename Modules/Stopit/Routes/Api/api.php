<?php

use Illuminate\Support\Facades\Route;
use Modules\Stopit\Providers\src\Http\Controllers\ExceptionController;
use Modules\Stopit\Providers\src\Http\Middleware\ApiTokenMiddleware;

Route::prefix('v1')->middleware(ApiTokenMiddleware::class)->group(function () {
    Route::post('/exceptions', [ExceptionController::class, 'store']);
});
