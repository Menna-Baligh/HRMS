<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ManagerAttendanceController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    // Route::post('/login', [AuthController::class,'login'])
    //     ->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
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
Route::middleware(['auth:api', 'check.active'])->group(function () {
    Route::middleware(['role:Owner|HR'])->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);
    });
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:create employee');
    Route::patch('/employees/profile', [EmployeeController::class, 'updateProfile']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::patch('/employees/{id}/hr-fields', [EmployeeController::class, 'updateHrFields'])->middleware('permission:edit hr fields');
    Route::patch('/employees/{id}/change-account-status', [EmployeeController::class, 'changeAccountStatus'])->middleware('permission:employee.change-account-status');
    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employee.view-all');

    Route::get('/departments', [DepartmentController::class, 'index'])->middleware('permission:department.view');
    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:department.create');
    Route::patch('/departments/{id}', [DepartmentController::class, 'update'])->middleware('permission:department.edit');
    Route::patch('/departments/{id}/change-status', [DepartmentController::class, 'changeStatus'])->middleware('permission:department.change-status');

    Route::get('/managers/employees', [ManagerController::class, 'employees'])->middleware('permission:manager.view-employees');

    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/history', [AttendanceController::class, 'history']);
    });
    Route::middleware(['role:Manager|Owner|HR'])->prefix('manager/attendance')->group(function () {
        Route::get('/today', [ManagerAttendanceController::class, 'today']);
        Route::get('/{employeeId}', [ManagerAttendanceController::class, 'show']);
    });
});
