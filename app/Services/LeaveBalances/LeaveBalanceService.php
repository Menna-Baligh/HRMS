<?php

namespace App\Services\LeaveBalances;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class LeaveBalanceService
{
    /**
     * Get leave balances for the authenticated user.
     */
    public function getMyBalances(
        User $user,
        ?int $year = null,
        ?int $leaveTypeId = null
    ): Collection {
        $year ??= now()->year;

        return LeaveBalance::query()
            ->with('leaveType')
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->when(
                $leaveTypeId !== null,
                fn ($query) => $query->where(
                    'leave_type_id',
                    $leaveTypeId
                )
            )
            ->orderBy('leave_type_id')
            ->get();
    }

    /**
     * Create balances for all active leave types
     * that require a balance.
     */
    public function createBalancesForUser(
        User $user,
        ?int $year = null
    ): Collection {
        $year ??= now()->year;

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->where('requires_balance', true)
            ->get();

        foreach ($leaveTypes as $leaveType) {
            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $leaveType->default_days,
                    'used_days' => 0,
                ]
            );
        }

        return $this->getMyBalances(
            user: $user,
            year: $year
        );
    }

    /**
     * Validate that the user has enough leave balance.
     */
    public function validateBalance(
        User $user,
        LeaveType $leaveType,
        float $requestedDays,
        ?int $year = null
    ): void {
        if (! $leaveType->requires_balance) {
            return;
        }

        $year ??= now()->year;

        $balance = LeaveBalance::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            throw new RuntimeException(
                __('leave_balances.balance_not_found')
            );
        }

        $remaining =
            (float) $balance->allocated_days -
            (float) $balance->used_days;

        if ($requestedDays > $remaining) {
            throw new RuntimeException(
                __('leave_balances.insufficient_balance', [
                    'remaining' => $remaining,
                ])
            );
        }
    }

    /**
     * Deduct leave balance after approval.
     */
    public function deductBalance(
        User $user,
        LeaveType $leaveType,
        float $days,
        int $year
    ): LeaveBalance {
        if (! $leaveType->requires_balance) {
            throw new RuntimeException(
                __('leave_balances.not_required')
            );
        }

        $balance = LeaveBalance::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            throw new RuntimeException(
                'Leave balance does not exist.'
            );
        }

        $remaining =
            (float) $balance->allocated_days -
            (float) $balance->used_days;

        if ($days > $remaining) {
            throw new RuntimeException(
                "Insufficient leave balance. Remaining balance: {$remaining} days."
            );
        }

        $balance->used_days += $days;
        $balance->save();

        return $balance->fresh();
    }
}