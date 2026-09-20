<?php

namespace App\Services\LeaveRequests;

use App\Enums\LeaveStatus;
use App\Models\LeaveDecision;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
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

    public function create(User $user, array $data): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        $days = $startDate->diffInDays($endDate) + 1;

        $leaveType = LeaveType::query()
            ->whereKey($data['leave_type_id'])
            ->where('is_active', true)
            ->first();

        if (! $leaveType) {
            throw new RuntimeException(
                __('leave_requests.leave_type_inactive')
            );
        }

        $hasOverlap = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                LeaveStatus::Pending->value,
                LeaveStatus::Approved->value,
            ])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($hasOverlap) {
            throw new RuntimeException(
                __('leave_requests.overlap')
            );
        }

        $this->leaveBalanceService->validateBalance(
            user: $user,
            leaveType: $leaveType,
            requestedDays: $days,
            year: $startDate->year
        );

        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveStatus::Pending->value,
        ]);
    }

    public function approve(
        LeaveRequest $leaveRequest,
        int $reviewerId
    ): LeaveRequest {
        return DB::transaction(function () use (
            $leaveRequest,
            $reviewerId
        ) {
            $leaveRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw new RuntimeException(
                    __('leave_requests.only_pending_approve')
                );
            }

            $user = $leaveRequest->user;
            $leaveType = $leaveRequest->leaveType;

            $previousStatus = $leaveRequest->status->value;

            $this->leaveBalanceService->deductBalance(
                user: $user,
                leaveType: $leaveType,
                days: (float) $leaveRequest->days,
                year: $leaveRequest->start_date->year
            );

            $leaveRequest->update([
                'status' => LeaveStatus::Approved->value,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            LeaveDecision::create([
                'leave_request_id' => $leaveRequest->id,
                'reviewer_id' => $reviewerId,
                'previous_status' => $previousStatus,
                'decision' => LeaveStatus::Approved->value,
                'reason' => null,
                'decided_at' => now(),
            ]);

            return $leaveRequest->fresh([
                'user',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ]);
        });
    }

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
            $leaveRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw new RuntimeException(
                    __('leave_requests.only_pending_reject')
                );
            }

            $previousStatus = $leaveRequest->status->value;

            $leaveRequest->update([
                'status' => LeaveStatus::Rejected->value,
                'rejection_reason' => $rejectionReason,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            LeaveDecision::create([
                'leave_request_id' => $leaveRequest->id,
                'reviewer_id' => $reviewerId,
                'previous_status' => $previousStatus,
                'decision' => LeaveStatus::Rejected->value,
                'reason' => $rejectionReason,
                'decided_at' => now(),
            ]);

            return $leaveRequest->fresh([
                'user',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ]);
        });
    }

    public function getUserHistory(
        User $user,
        ?string $status = null,
        ?int $leaveTypeId = null,
        ?int $year = null
    ): Collection {
        return LeaveRequest::query()
            ->with([
                'user',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ])
            ->where('user_id', $user->id)
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

    public function getDetails(LeaveRequest $leaveRequest): LeaveRequest
{
    return $leaveRequest->load([
        'user',
        'leaveType',
        'reviewer',
        'decisions.reviewer',
        'files',
    ]);
} 


// Manager Pending Leave Queue 

public function getManagerPendingQueue(User $manager): Collection
{
    return LeaveRequest::query()
        ->with([
            'user',
            'leaveType',
            'reviewer',
            'decisions.reviewer',
            'files',
        ])
        ->where('status', LeaveStatus::Pending->value)
        ->whereHas(
            'user',
            fn ($query) => $query->where('manager_id', $manager->id)
        )
        ->orderBy('created_at')
        ->get();
}
// Hr Pending Queue
public function getHrPendingQueue(): Collection
{
    return LeaveRequest::query()
        ->with([
            'user',
            'leaveType',
            'reviewer',
            'decisions.reviewer',
            'files',
        ])
        ->where('status', LeaveStatus::Pending->value)
        ->orderBy('created_at')
        ->get();
}

// Decision History

public function getDecisionHistory(
    LeaveRequest $leaveRequest
): Collection {
    return $leaveRequest->decisions()
        ->with('reviewer')
        ->orderByDesc('decided_at')
        ->get();
}
}