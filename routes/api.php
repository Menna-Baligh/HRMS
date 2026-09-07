<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class,'login'])
            ->middleware('throttle:5,1');
    });


