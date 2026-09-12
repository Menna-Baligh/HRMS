<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ManagerController;

use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);


Route::prefix('auth')->group(function () {
    // Route::post('/login', [AuthController::class,'login'])
    //     ->middleware('throttle:5,1');

    Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
});

Route::prefix('tasks')->middleware('auth:api')->group(function () {
        // create task
        Route::post('/', [TaskController::class, 'store']);
        // update task
        Route::put('/{task}', [TaskController::class, 'update']);
        // assign task to employee
        Route::post('/{task}/assign', [TaskController::class, 'assign']);
        // progress
        Route::patch('/{task}/progress',[TaskController::class, 'updateProgress']);
        //change status
        Route::patch('/{task}/status',[TaskController::class, 'updateStatus']);
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
use App\Http\Controllers\Api\EmployeeController;

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
