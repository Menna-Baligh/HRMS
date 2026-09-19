<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeEvaluationController;
use App\Http\Controllers\Api\EmployeePerformanceController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\HrAttendanceController;
use App\Http\Controllers\Api\HrEvaluationSetupController;
use App\Http\Controllers\Api\HrGoalController;
use App\Http\Controllers\Api\HrPerformanceController;
use App\Http\Controllers\Api\LeaveBalanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\ManagerAttendanceController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\ManagerPerformanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\V1\Calendar\LeaveCalendarController;
use App\Http\Controllers\Api\V1\HR\HRLeaveQueueController;
use App\Http\Controllers\Api\V1\LeaveDecisionHistory\LeaveDecisionHistoryController;
use App\Http\Controllers\Api\V1\LeaveRequest\LeaveApprovalController;
use App\Http\Controllers\Api\V1\Manager\ManagerLeaveQueueController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('set.app.language')->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');

    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');

    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('google')->group(function () {

        Route::get('/redirect', [GoogleAuthController::class, 'redirect']);

        Route::get('/callback', [GoogleAuthController::class, 'callback']);
    });

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| leave Management Routes
|--------------------------------------------------------------------------
*/


Route::prefix('leaves')->middleware('auth:api')->group(function () {
    // new leave type
    Route::post('/leave-types', [LeaveTypeController::class,'store',]);
    //get all leave types
    Route::get('/leave-types', [LeaveTypeController::class,'index',]); 
    //update leave type
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class,'update',]);
    //activate leave
    Route::patch('/leave-types/{leaveType}/activate', [LeaveTypeController::class,'activate',]);
    //deactivate
    Route::patch('/leave-types/{leaveType}/deactivate', [LeaveTypeController::class,'deactivate',]);
    //Leave Balances
    Route::get('/leave-balances', [LeaveBalanceController::class,'index',]);
    // create leave
    Route::post( '/leave-requests', [LeaveRequestController::class, 'store'] );
    //approve leave
    Route::patch('/leave-requests/{leaveRequest}/approve', [ LeaveRequestController::class, 'approve' ]);
    //reject leave
    Route::patch('/leave-requests/{leaveRequest}/reject', [ LeaveRequestController::class, 'reject' ]);
    // leave history
    Route::get('/leave-requests', [ LeaveRequestController::class,'history']);
});

/*
|--------------------------------------------------------------------------
| Task Management Routes
|--------------------------------------------------------------------------
*/


Route::prefix('tasks')->middleware('auth:api')->group(function () {
        // Create task
        Route::post('/', [TaskController::class, 'store']);
        // Update task
        Route::put('/{task}', [TaskController::class, 'update']);
        // Assign task to employee
        Route::post('/{task}/assign', [TaskController::class, 'assign']);
        // Update task progress
        Route::patch('/{task}/progress', [TaskController::class, 'updateProgress']);
        // Update task status
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus']);
        // Submit a task
        Route::post('/{task}/submissions', [SubmissionController::class, 'store']);
        // Attach file to submission
        Route::post('/submissions/{submission}/attachments',[SubmissionController::class, 'attachFile']);
        // Review queue
        Route::get('/submissions/review',[SubmissionController::class, 'reviewQueue']);
        // Submission details
        Route::get('/submissions/{submission}',[SubmissionController::class, 'show']);
        // Approve submission
        Route::patch('/submissions/{submission}/approve',[SubmissionController::class, 'approve']);
        // Reject submission
        Route::patch('/submissions/{submission}/reject',[SubmissionController::class, 'reject']);
        // Request changes
        Route::patch('/submissions/{submission}/request-changes',[SubmissionController::class, 'requestChanges']);
        // Resubmit
        Route::post('/submissions/{submission}/resubmit',[SubmissionController::class, 'resubmit']);
    });

/*
|--------------------------------------------------------------------------
| Company Location Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')
    ->prefix('locations')
    ->group(function () {
        // Create company location
        Route::post('company/location',[CompanyLocationController::class, 'store']);
        // Update company location
        Route::put('company/location/{id}',[CompanyLocationController::class, 'update']);
        // Deactivate company location
        Route::patch('company/location/{id}/deactivate',[CompanyLocationController::class, 'deactivate']);
        // Activate company location
        Route::patch('company/location/{id}/activate',[CompanyLocationController::class, 'activate']);
        // Get active company location
        Route::get('company/location/active',[CompanyLocationController::class, 'activeLocation']
        );
    });

/*
|--------------------------------------------------------------------------
| Protected Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'check.active'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:Owner|HR'])->group(function () {

        Route::get(
            '/permissions',
            [PermissionController::class, 'index']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Employees
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/employees',
        [EmployeeController::class, 'store']
    )->middleware('permission:create employee');

    Route::patch(
        '/employees/profile',
        [EmployeeController::class, 'updateProfile']
    );

    Route::get(
        '/employees/{id}',
        [EmployeeController::class, 'show']
    );

    Route::patch(
        '/employees/{id}/hr-fields',
        [EmployeeController::class, 'updateHrFields']
    )->middleware('permission:edit hr fields');

    Route::patch(
        '/employees/{id}/change-account-status',
        [EmployeeController::class, 'changeAccountStatus']
    )->middleware('permission:employee.change-account-status');

    Route::get(
        '/employees',
        [EmployeeController::class, 'index']
    )->middleware('permission:employee.view-all');

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/departments',
        [DepartmentController::class, 'index']
    )->middleware('permission:department.view');

    Route::post(
        '/departments',
        [DepartmentController::class, 'store']
    )->middleware('permission:department.create');

    Route::patch(
        '/departments/{id}',
        [DepartmentController::class, 'update']
    )->middleware('permission:department.edit');

    Route::patch(
        '/departments/{id}/change-status',
        [DepartmentController::class, 'changeStatus']
    )->middleware('permission:department.change-status');

    /*
    |--------------------------------------------------------------------------
    | Manager
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/managers/employees',
        [ManagerController::class, 'employees']
    )->middleware('permission:manager.view-employees');

    /*
    |--------------------------------------------------------------------------
    | Employee Attendance
    |--------------------------------------------------------------------------
    */

    Route::prefix('attendance')->group(function () {

        Route::get(
            '/today',
            [AttendanceController::class, 'today']
        );

        Route::post(
            '/check-in',
            [AttendanceController::class, 'checkIn']
        );

        Route::post(
            '/check-out',
            [AttendanceController::class, 'checkOut']
        );

        Route::get(
            '/history',
            [AttendanceController::class, 'history']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Manager Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:Manager|Owner|HR'])
        ->prefix('manager')
        ->group(function () {

            Route::prefix('/attendance')->group(function () {

                Route::get(
                    '/today',
                    [ManagerAttendanceController::class, 'today']
                );

                Route::get(
                    '/{employeeId}',
                    [ManagerAttendanceController::class, 'show']
                );
            });

            Route::get(
                '/team-goals',
                [ManagerController::class, 'teamGoals']
            );
        });

    /*
    |--------------------------------------------------------------------------
    | HR Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:HR|Owner'])
        ->prefix('hr')
        ->group(function () {

            Route::prefix('/attendance')->group(function () {

                Route::get(
                    '/daily',
                    [HrAttendanceController::class, 'daily']
                );

                Route::get(
                    '/exceptions',
                    [HrAttendanceController::class, 'exceptions']
                );

                Route::get(
                    '/monthly-summary',
                    [HrAttendanceController::class, 'monthlySummary']
                );

                Route::get(
                    '/export',
                    [HrAttendanceController::class, 'export']
                );
            });

            Route::get(
                '/evaluations',
                [HrEvaluationSetupController::class, 'index']
            );

            Route::get(
                '/goals',
                [HrGoalController::class, 'index']
            );

            Route::get(
                '/company-performance',
                [HrPerformanceController::class, 'companyDashboard']
            );
        });

    /*
    |--------------------------------------------------------------------------
    | Evaluation Management
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:Manager|HR|Owner'])->group(function () {

        // Evaluation periods
        Route::get(
            '/evaluation-periods',
            [HrEvaluationSetupController::class, 'listPeriods']
        );

        Route::post(
            '/evaluation-periods',
            [HrEvaluationSetupController::class, 'storePeriod']
        );

        Route::patch(
            '/evaluation-periods/{id}/toggle-status',
            [HrEvaluationSetupController::class, 'togglePeriodStatus']
        );

        // Evaluation categories
        Route::get(
            '/evaluation-categories',
            [HrEvaluationSetupController::class, 'listCategories']
        );

        Route::post(
            '/evaluation-categories',
            [HrEvaluationSetupController::class, 'storeCategory']
        );

        // Evaluations
        Route::post(
            '/evaluations',
            [EvaluationController::class, 'store']
        );

        Route::put(
            '/evaluations/{id}',
            [EvaluationController::class, 'update']
        );

        Route::patch(
            '/evaluations/{id}/complete',
            [EvaluationController::class, 'complete']
        );

        Route::get(
            '/manager/evaluations',
            [EvaluationController::class, 'managerEvaluations']
        );

        // Manager performance
        Route::get(
            '/manager/team-performance',
            [ManagerPerformanceController::class, 'teamDashboard']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Employee Evaluation & Performance
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/employee/evaluations',
        [EmployeeEvaluationController::class, 'index']
    );

    Route::get(
        '/employee/performance',
        [EmployeePerformanceController::class, 'dashboard']
    );
});

Route::middleware(['auth:api', 'check.active', 'set.app.language'])->group(function () {
    Route::prefix('goals')->group(function () {

        Route::get(
            '/',
            [GoalController::class, 'index']
        );

        Route::post(
            '/',
            [GoalController::class, 'store']
        );

        Route::get(
            '/{id}',
            [GoalController::class, 'show']
        );

        Route::put(
            '/{id}',
            [GoalController::class, 'update']
        );

        Route::patch(
            '/{id}/progress',
            [GoalController::class, 'updateProgress']
        );

        Route::patch(
            '/{id}/complete',
            [GoalController::class, 'complete']
        );
    });

    Route::get('/employee/performance', [EmployeePerformanceController::class, 'dashboard'])->middleware('permission:'.PermissionEnum::EMPLOYEE_PERFORMANCE_DASHBOARD->value);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken']);
    });

    Route::prefix('files')->group(function () {
        Route::get('/{file}/download', [FileController::class, 'download'])->name('files.download')->middleware('permission:'.PermissionEnum::FILE_DOWNLOAD->value);
        Route::delete('/{file}', [FileController::class, 'destroy'])->name('files.destroy')->middleware('permission:'.PermissionEnum::FILE_DELETE->value);
    });
});



