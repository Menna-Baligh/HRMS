<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    /**
     * Verify that the user has enough remaining balance for the given leave type and year.
     *
     * @throws \DomainException if insufficient balance.
     */
    public function checkSufficient(
        User $user,
        LeaveType $leaveType,
        float $requestedDays,
        int $year,
    ): void {
        if (! $leaveType->requires_balance) {
            return;
        }

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            throw new \DomainException(
                "No leave balance record found for user [{$user->id}] on leave type [{$leaveType->name}] for year [{$year}]."
            );
        }

        $remaining = (float) $balance->allocated_days - (float) $balance->used_days;

        if ($requestedDays > $remaining) {
            throw new \DomainException(
                "Insufficient leave balance. Requested: {$requestedDays} day(s), remaining: {$remaining} day(s)."
            );
        }
    }

    /**
     * Deduct days from the balance record atomically (pessimistic lock).
     *
     * Must be called inside a DB::transaction().
     *
     * @throws \DomainException if balance is now insufficient (race-condition guard).
     */
    public function deduct(
        User $user,
        LeaveType $leaveType,
        float $days,
        int $year,
    ): void {
        if (! $leaveType->requires_balance) {
            return;
        }

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();

        $remaining = (float) $balance->allocated_days - (float) $balance->used_days;

        if ($days > $remaining) {
            throw new \DomainException(
                "Race condition: insufficient balance. Remaining: {$remaining} day(s), required: {$days} day(s)."
            );
        }

        $balance->increment('used_days', $days);
    }

    /**
     * Refund days back to the balance record (on cancellation/rejection after approval).
     *
     * Must be called inside a DB::transaction().
     */
    public function refund(
        User $user,
        LeaveType $leaveType,
        float $days,
        int $year,
    ): void {
        if (! $leaveType->requires_balance) {
            return;
        }

        LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail()
            ->decrement('used_days', $days);
    }
}
