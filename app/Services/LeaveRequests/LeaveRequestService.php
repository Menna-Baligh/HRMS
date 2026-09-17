<?php

namespace App\Services\LeaveRequests;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveDecision;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalances\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveRequestService
{
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService
    ) {
    }

    /**
     * Create a new leave request for an employee.
     */
    public function create(
        Employee $employee,
        array $data
    ): LeaveRequest {
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        $days = $startDate->diffInDays($endDate) + 1;

        // Prevent overlapping pending or approved leave requests.
        $hasOverlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [
                LeaveStatus::Pending->value,
                LeaveStatus::Approved->value,
            ])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($hasOverlap) {
            throw new RuntimeException(
                'You already have a leave request overlapping with these dates.'
            );
        }

        $leaveType = LeaveType::findOrFail(
            $data['leave_type_id']
        );

        // Validate the employee's available balance.
        $this->leaveBalanceService->validateBalance(
            $employee,
            $leaveType,
            $days,
            $startDate->year
        );

        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveStatus::Pending->value,
        ]);
    }

    /**
     * Approve a pending leave request.
     */
    public function approve(LeaveRequest $leaveRequest,int $reviewerId): LeaveRequest {
        return DB::transaction(function () use (
            $leaveRequest,
            $reviewerId
        ) {
            // Lock the leave request to prevent double approval.
            $leaveRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw new RuntimeException(
                    'Only pending leave requests can be approved.'
                );
            }

            $employee = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;

            $previousStatus = $leaveRequest->status->value;

            // Deduct the balance only when the request is approved.
            $this->leaveBalanceService->deductBalance(
                $employee,
                $leaveType,
                (float) $leaveRequest->days,
                $leaveRequest->start_date->year
            );

            $leaveRequest->update([
                'status' => LeaveStatus::Approved->value,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            // Store the approval decision in the history table.
            LeaveDecision::create([
                'leave_request_id' => $leaveRequest->id,
                'reviewer_id' => $reviewerId,
                'previous_status' => $previousStatus,
                'decision' => LeaveStatus::Approved->value,
                'reason' => null,
                'decided_at' => now(),
            ]);

            return $leaveRequest->fresh([
                'employee',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ]);
        });
    }

    /**
     * Reject a pending leave request.
     */
    public function reject(
        LeaveRequest $leaveRequest,
        int $reviewerId,
        string $rejectionReason
    ): LeaveRequest {
        return DB::transaction(function () use (
            $leaveRequest,
            $reviewerId,
            $rejectionReason
        ) {
            // Lock the leave request to prevent concurrent decisions.
            $leaveRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw new RuntimeException(
                    'Only pending leave requests can be rejected.'
                );
            }

            $previousStatus = $leaveRequest->status->value;

            $leaveRequest->update([
                'status' => LeaveStatus::Rejected->value,
                'rejection_reason' => $rejectionReason,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            // Store the rejection decision in the history table.
            LeaveDecision::create([
                'leave_request_id' => $leaveRequest->id,
                'reviewer_id' => $reviewerId,
                'previous_status' => $previousStatus,
                'decision' => LeaveStatus::Rejected->value,
                'reason' => $rejectionReason,
                'decided_at' => now(),
            ]);

            return $leaveRequest->fresh([
                'employee',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ]);
        });
    }
    /**

* Get the authenticated employee's leave history.
  */
  public function getEmployeeHistory( Employee $employee,?string $status = null,?int $leaveTypeId = null,?int $year = null): Collection 
  {
    return LeaveRequest::query()
    ->with([
    'leaveType',
    'reviewer',
    'decisions.reviewer',
    ])
    ->where('employee_id', $employee->id)
    ->when(
    $status !== null,
    fn ($query) => $query->where('status', $status)
    )
    ->when(
    $leaveTypeId !== null,
    fn ($query) => $query->where('leave_type_id', $leaveTypeId)
    )
    ->when(
    $year !== null,
    fn ($query) => $query->whereYear('start_date', $year)
    )
    ->orderByDesc('start_date')
    ->get();
    }
  
}
