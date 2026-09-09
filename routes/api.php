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
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\PermissionController;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['auth:api'])->group(function () {
    Route::middleware(['role:Owner|HR'])->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);
    });
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:create employee');
    Route::patch('/employees/profile', [EmployeeController::class, 'updateProfile']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::patch('/employees/{id}/hr-fields', [EmployeeController::class, 'updateHrFields'])->middleware('permission:edit hr fields');
});
