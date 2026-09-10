<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case VIEW_EMPLOYEES = 'view employees';
    case CREATE_EMPLOYEE = 'create employee';
    case EDIT_HR_FIELDS = 'edit hr fields';
    case EDIT_SELF_PROFILE = 'edit self profile';
    case MANAGE_DEPARTMENTS = 'manage departments';
    case EMPLOYEE_CHANGE_ACCOUNT_STATUS = 'employee.change-account-status';
}
