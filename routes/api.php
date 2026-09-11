<?php

use App\Http\Controllers\Api\V1\Calendar\LeaveCalendarController;
use App\Http\Controllers\Api\V1\HR\HRLeaveQueueController;
use App\Http\Controllers\Api\V1\LeaveBalance\LeaveBalanceController;
use App\Http\Controllers\Api\V1\LeaveDecisionHistory\LeaveDecisionHistoryController;
use App\Http\Controllers\Api\V1\LeaveRequest\LeaveApprovalController;
use App\Http\Controllers\Api\V1\LeaveRequest\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveType\LeaveTypeController;
use App\Http\Controllers\Api\V1\Manager\ManagerLeaveQueueController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // ─── Authentication ───────────────────────────────────────────────────────
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware('jwt.auth')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        });
    });

    // ─── Protected Routes ─────────────────────────────────────────────────────
    Route::middleware('jwt.auth')->group(function (): void {
        // Leave Types (Read: All authenticated, Write: HR/Owner via Policy)
        Route::apiResource('leave-types', LeaveTypeController::class);

        // Leave Balances
        Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');

        // Leave Requests (Employee own & actions)
        Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
        Route::get('leave-requests/{leave_request}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
        Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

        // Approval Workflow (Protected by Gate/Policies inside controller)
        Route::prefix('leave-requests/{leave_request}')->group(function (): void {
            Route::post('approve-manager', [LeaveApprovalController::class, 'approveByManager'])->name('leave-requests.approve-manager');
            Route::post('approve-hr', [LeaveApprovalController::class, 'approveByHR'])->name('leave-requests.approve-hr');
            Route::post('reject', [LeaveApprovalController::class, 'reject'])->name('leave-requests.reject');
            Route::get('history', [LeaveDecisionHistoryController::class, 'index'])->name('leave-requests.history');
        });

        // Manager Queue (Direct reports only)
        Route::middleware('role:Manager,Owner,HR')->prefix('manager')->group(function (): void {
            Route::get('leave-requests', [ManagerLeaveQueueController::class, 'index'])->name('manager.leave-requests.index');
        });

        // HR Queue (Full visibility with multi-parameter filtering)
        Route::middleware('role:HR,Owner')->prefix('hr')->group(function (): void {
            Route::get('leave-requests', [HRLeaveQueueController::class, 'index'])->name('hr.leave-requests.index');
        });

        // Leave Calendar (Approved leaves view)
        Route::get('calendar/leaves', [LeaveCalendarController::class, 'index'])->name('calendar.leaves');
    });
});
