<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Route::post('/login', [AuthController::class,'login'])
    //     ->middleware('throttle:5,1');
    Route::post('/register',[AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/forget-password',[AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp',[AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset',[AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp',[AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
});