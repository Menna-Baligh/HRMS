<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    /** HR and Owner can create leave types. */
    public function create(User $user): bool
    {
        return $user->isHrOrAbove();
    }

    /** HR and Owner can update leave types. */
    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->isHrOrAbove();
    }

    /** HR and Owner can delete leave types. */
    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->isHrOrAbove();
    }
}
