<?php

namespace App\Policies;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Any authenticated user can view their own request.
     * Managers can view requests belonging to their subordinates.
     * HR and Owner can view all requests.
     */
    public function view(User $user, LeaveRequest $request): bool
    {
        if ($user->isHrOrAbove()) {
            return true;
        }

        if ($user->isManager() && $request->user->manager_id === $user->id) {
            return true;
        }

        return $user->id === $request->user_id;
    }

    /** Only the employee who submitted can cancel (if still in a cancellable status). */
    public function cancel(User $user, LeaveRequest $request): bool
    {
        return $user->id === $request->user_id && $request->status->isCancellable();
    }

    /**
     * Manager or Owner can approve a PENDING request.
     * The request must belong to one of the manager's subordinates (unless Owner).
     */
    public function approveByManager(User $user, LeaveRequest $request): bool
    {
        if ($request->status !== LeaveStatus::Pending) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isManager()) {
            return $request->user->manager_id === $user->id;
        }

        return false;
    }

    /**
     * HR or Owner can give final approval on a MANAGER-APPROVED request.
     */
    public function approveByHR(User $user, LeaveRequest $request): bool
    {
        if ($request->status !== LeaveStatus::ApprovedByManager) {
            return false;
        }

        return $user->isHrOrAbove();
    }

    /**
     * Who can reject:
     * - Manager / Owner: from PENDING
     * - HR / Owner: from APPROVED_BY_MANAGER
     */
    public function reject(User $user, LeaveRequest $request): bool
    {
        if ($request->status === LeaveStatus::Pending) {
            if ($user->isOwner()) {
                return true;
            }

            if ($user->isManager()) {
                return $request->user->manager_id === $user->id;
            }
        }

        if ($request->status === LeaveStatus::ApprovedByManager) {
            return $user->isHrOrAbove();
        }

        return false;
    }
}
