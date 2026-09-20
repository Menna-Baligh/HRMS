<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // ==========================================
    // 1. Employees Management
    // ==========================================
    case EMPLOYEE_CREATE = 'create employee';
    case EMPLOYEE_EDIT_HR_FIELDS = 'edit hr fields';
    case EMPLOYEE_CHANGE_ACCOUNT_STATUS = 'employee.change-account-status';
    case EMPLOYEE_VIEW_ALL = 'employee.view-all';
    case EMPLOYEE_VIEW_PROFILE = 'employee.view-profile';
    case EMPLOYEE_UPDATE_PROFILE = 'employee.update-profile';

    // ==========================================
    // 2. Departments Management
    // ==========================================
    case DEPARTMENT_VIEW = 'department.view';
    case DEPARTMENT_CREATE = 'department.create';
    case DEPARTMENT_EDIT = 'department.edit';
    case DEPARTMENT_CHANGE_STATUS = 'department.change-status';

    // ==========================================
    // 3. Manager Actions
    // ==========================================
    case MANAGER_VIEW_EMPLOYEES = 'manager.view-employees';
    case MANAGER_VIEW_ATTENDANCE = 'manager.view-attendance';
    case MANAGER_VIEW_TEAM_GOALS = 'manager.view-team-goals';

    // ==========================================
    // 4. Tasks & Submissions
    // ==========================================
    case TASK_CREATE = 'task.create';
    case TASK_UPDATE = 'task.update';
    case TASK_ASSIGN = 'task.assign';
    case TASK_UPDATE_PROGRESS = 'task.update-progress';
    case TASK_UPDATE_STATUS = 'task.update-status';
    case SUBMISSION_CREATE = 'submission.create';
    case SUBMISSION_ATTACH_FILE = 'submission.attach-file';
    case SUBMISSION_REVIEW_QUEUE = 'submission.review-queue';
    case SUBMISSION_VIEW = 'submission.view';
    case SUBMISSION_APPROVE = 'submission.approve';
    case SUBMISSION_REJECT = 'submission.reject';
    case SUBMISSION_REQUEST_CHANGES = 'submission.request-changes';
    case SUBMISSION_RESUBMIT = 'submission.resubmit';

    // ==========================================
    // 5. Company Locations
    // ==========================================
    case LOCATION_CREATE = 'location.create';
    case LOCATION_UPDATE = 'location.update';
    case LOCATION_DEACTIVATE = 'location.deactivate';
    case LOCATION_ACTIVATE = 'location.activate';
    case LOCATION_VIEW_ACTIVE = 'location.view-active';

    // ==========================================
    // 6. Attendance & HR Dashboard
    // ==========================================
    case ATTENDANCE_CHECKIN_CHECKOUT = 'attendance.checkin-checkout';
    case ATTENDANCE_VIEW_HISTORY = 'attendance.view-history';
    case HR_ATTENDANCE_VIEW_DAILY = 'hr.attendance.view-daily';
    case HR_ATTENDANCE_VIEW_EXCEPTIONS = 'hr.attendance.view-exceptions';
    case HR_ATTENDANCE_VIEW_SUMMARY = 'hr.attendance.view-summary';
    case HR_ATTENDANCE_EXPORT = 'hr.attendance.export';

    // ==========================================
    // 7. Goals
    // ==========================================
    case GOAL_VIEW_OWN = 'goal.view-own';
    case GOAL_CREATE = 'goal.create';
    case GOAL_UPDATE = 'goal.update';
    case GOAL_UPDATE_PROGRESS = 'goal.update-progress';
    case GOAL_COMPLETE = 'goal.complete';
    case HR_GOALS_OVERVIEW = 'hr.goals.overview';

    // ==========================================
    // 8. Evaluations & Performance
    // ==========================================
    case EVALUATION_MANAGE_SETUP = 'evaluation.manage-setup';
    case EVALUATION_CREATE = 'evaluation.create';
    case EVALUATION_UPDATE = 'evaluation.update';
    case EVALUATION_COMPLETE = 'evaluation.complete';
    case EVALUATION_VIEW_MANAGER = 'evaluation.view-manager';
    case EVALUATION_VIEW_EMPLOYEE = 'evaluation.view-employee';
    case HR_PERFORMANCE_COMPANY = 'hr.performance.company';
    case MANAGER_PERFORMANCE_TEAM = 'manager.performance.team';
    case EMPLOYEE_PERFORMANCE_DASHBOARD = 'employee.performance.dashboard';

    // ==========================================
    // 9. Leave Management 
    // ==========================================
    case LEAVE_TYPE_VIEW       = 'leave_type.view';
    case LEAVE_TYPE_MANAGE     = 'leave_type.manage'; 
    case LEAVE_BALANCE_VIEW    = 'leave_balance.view';
    case LEAVE_REQUEST_VIEW_OWN= 'leave_request.view_own';
    case LEAVE_REQUEST_CREATE  = 'leave_request.create';
    case LEAVE_REQUEST_CANCEL  = 'leave_request.cancel';
    case LEAVE_APPROVE_MANAGER = 'leave.approve_manager';
    case LEAVE_APPROVE_HR      = 'leave.approve_hr';
    case LEAVE_REJECT          = 'leave.reject';
    case LEAVE_VIEW_HISTORY    = 'leave.view_history';
    case LEAVE_QUEUE_MANAGER   = 'leave.queue_manager';
    case LEAVE_QUEUE_HR        = 'leave.queue_hr';
    case LEAVE_CALENDAR_VIEW   = 'leave.calendar_view';

    // ==========================================
    // 10. System, Files & Permissions
    // ==========================================
    case PERMISSION_VIEW_ALL = 'permission.view-all';
    case FILE_DOWNLOAD = 'file.download';
    case FILE_DELETE = 'file.delete';

    public function defaultRoles(): array
    {
        return match ($this) {
            // Owner & HR Only
            self::EMPLOYEE_CREATE,
            self::EMPLOYEE_EDIT_HR_FIELDS,
            self::EMPLOYEE_CHANGE_ACCOUNT_STATUS,
            self::EMPLOYEE_VIEW_ALL,
            self::DEPARTMENT_CREATE,
            self::DEPARTMENT_EDIT,
            self::DEPARTMENT_CHANGE_STATUS,
            self::LOCATION_CREATE,
            self::LOCATION_UPDATE,
            self::LOCATION_DEACTIVATE,
            self::LOCATION_ACTIVATE,
            self::HR_ATTENDANCE_VIEW_DAILY,
            self::HR_ATTENDANCE_VIEW_EXCEPTIONS,
            self::HR_ATTENDANCE_VIEW_SUMMARY,
            self::HR_ATTENDANCE_EXPORT,
            self::HR_GOALS_OVERVIEW,
            self::HR_PERFORMANCE_COMPANY,
            self::PERMISSION_VIEW_ALL,
            self::LEAVE_TYPE_MANAGE,
            self::LEAVE_APPROVE_HR,
            self::LEAVE_REJECT,
            self::LEAVE_QUEUE_HR => ['HR', 'Owner'],

            // Manager, HR & Owner
            self::MANAGER_VIEW_EMPLOYEES,
            self::MANAGER_VIEW_ATTENDANCE,
            self::MANAGER_VIEW_TEAM_GOALS,
            self::TASK_CREATE,
            self::TASK_UPDATE,
            self::TASK_ASSIGN,
            self::SUBMISSION_REVIEW_QUEUE,
            self::SUBMISSION_APPROVE,
            self::SUBMISSION_REJECT,
            self::SUBMISSION_REQUEST_CHANGES,
            self::EVALUATION_MANAGE_SETUP,
            self::EVALUATION_CREATE,
            self::EVALUATION_UPDATE,
            self::EVALUATION_COMPLETE,
            self::EVALUATION_VIEW_MANAGER,
            self::MANAGER_PERFORMANCE_TEAM,
            self::LEAVE_APPROVE_MANAGER,
            self::LEAVE_QUEUE_MANAGER => ['Manager', 'HR', 'Owner'],

            // All Roles (Owner, HR, Manager, Employee)
            self::EMPLOYEE_VIEW_PROFILE,
            self::EMPLOYEE_UPDATE_PROFILE,
            self::DEPARTMENT_VIEW,
            self::TASK_UPDATE_PROGRESS,
            self::TASK_UPDATE_STATUS,
            self::SUBMISSION_CREATE,
            self::SUBMISSION_ATTACH_FILE,
            self::SUBMISSION_VIEW,
            self::SUBMISSION_RESUBMIT,
            self::LOCATION_VIEW_ACTIVE,
            self::ATTENDANCE_CHECKIN_CHECKOUT,
            self::ATTENDANCE_VIEW_HISTORY,
            self::GOAL_VIEW_OWN,
            self::GOAL_CREATE,
            self::GOAL_UPDATE,
            self::GOAL_UPDATE_PROGRESS,
            self::GOAL_COMPLETE,
            self::EVALUATION_VIEW_EMPLOYEE,
            self::EMPLOYEE_PERFORMANCE_DASHBOARD,
            self::LEAVE_BALANCE_VIEW,
            self::LEAVE_REQUEST_VIEW_OWN,
            self::LEAVE_REQUEST_CREATE,
            self::LEAVE_REQUEST_CANCEL,
            self::LEAVE_VIEW_HISTORY,
            self::LEAVE_TYPE_VIEW,
            self::LEAVE_CALENDAR_VIEW,
            self::FILE_DOWNLOAD,
            self::FILE_DELETE => ['Owner', 'HR', 'Manager', 'Employee'],

            default => ['Owner', 'HR'],
        };
    }
    public function label(): string
    {
        return __('permissions.' . $this->value);
    }
}
