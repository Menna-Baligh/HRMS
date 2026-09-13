<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function view(User $authUser, Employee $targetEmployee): bool
    {
        if ($authUser->hasRole(['Owner', 'HR'])) {
            return true;
        }

        if ($authUser->employee?->id === $targetEmployee->id) {
            return true;
        }

        if ($authUser->hasRole('Manager') && $targetEmployee->manager_id === $authUser->employee?->id) {
            return true;
        }

        return false;
    }

    public function updateHrFields(User $authUser): bool
    {
        return $authUser->hasPermissionTo(PermissionEnum::EDIT_HR_FIELDS->value);
    }
}
