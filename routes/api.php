<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
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


// Company Location 

// Create company location
Route::post('company/location',[CompanyLocationController::class, 'store']);

// Update company location
Route::put('company/location/{id}',[CompanyLocationController::class, 'update']);

// Deactivate company location
Route::patch('company/location/{id}/deactivate',[CompanyLocationController::class, 'deactivate']);

// Activate company location
Route::patch('company/location/{id}/activate',[CompanyLocationController::class, 'activate']);
// Get active company location
Route::get('company/location/active',[CompanyLocationController::class, 'activeLocation']);

// use App\Http\Controllers\Api\AuthController;
// use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('google')->group(function () {
        Route::get('/redirect', [GoogleAuthController::class, 'redirect']);
        Route::get('/callback', [GoogleAuthController::class, 'callback']);
    });

});

