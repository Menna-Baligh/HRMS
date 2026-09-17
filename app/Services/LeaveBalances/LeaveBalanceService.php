<?php

namespace App\Services\LeaveBalances;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class LeaveBalanceService
{
    public function getMyBalances(
        Employee $employee,
        ?int $year = null,
        ?int $leaveTypeId = null
    ): Collection {
        $year ??= now()->year;

        return LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
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

    public function createBalancesForEmployee(
        Employee $employee,
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
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $leaveType->default_days,
                    'used_days' => 0,
                ]
            );
        }

        return $this->getMyBalances($employee, $year);
    }

    public function validateBalance(
        Employee $employee,
        LeaveType $leaveType,
        float $requestedDays,
        ?int $year = null
    ): void {
        if (! $leaveType->requires_balance) {
            return;
        }

        $year ??= now()->year;

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            throw new RuntimeException(
                'Leave balance does not exist for this employee.'
            );
        }

        $remaining =
            (float) $balance->allocated_days -
            (float) $balance->used_days;

        if ($requestedDays > $remaining) {
            throw new RuntimeException(
                "Insufficient leave balance. Remaining balance: {$remaining} days."
            );
        }
    }

    public function deductBalance(
        Employee $employee,
        LeaveType $leaveType,
        float $days,
        int $year
    ): LeaveBalance {
        if (! $leaveType->requires_balance) {
            throw new RuntimeException(
                'This leave type does not require a balance.'
            );
        }

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
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