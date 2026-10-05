<?php

namespace App\Services\LeaveRequests;

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Models\LeaveDecision;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LeaveBalances\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaveRequestService
{
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService,
        protected AuditService $auditService
    ) {}

    public function create(User $user, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data) {
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
    

            // dd([
            //     'user_id' => $user->id,
            //     'user_name' => $user->name,
            //     'user_email' => $user->email,
            //     'user_role' => $user->role?->value ?? $user->role,
            //     'leave_type_id' => $leaveType->id,
            //     'year' => $startDate->year,
            //     'balance' => \App\Models\LeaveBalance::where('user_id', $user->id)
            //         ->where('leave_type_id', $leaveType->id)
            //         ->where('year', $startDate->year)
            //         ->first(),
            // ]);


            $this->leaveBalanceService->validateBalance(
                user: $user,
                leaveType: $leaveType,
                requestedDays: $days,
                year: $startDate->year
            );
    
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'reason' => $data['reason'] ?? null,
                'status' => LeaveStatus::Pending->value,
            ]);
    
              // record audit
            $this->auditService->record(
                actor: $user,
                action: AuditAction::LEAVE_CREATED,
                entity: $leaveRequest,
                metadata: [
                    'status' => LeaveStatus::Pending->value,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'days' => $days,
                ],
            );
    
            return $leaveRequest;
        });
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

            // Record the approval in the unified audit log.
            $this->auditService->record(
                actor: User::findOrFail($reviewerId),
                action: AuditAction::LEAVE_APPROVED,
                entity: $leaveRequest,
                metadata: [
                    'old_status' => $previousStatus,
                    'new_status' => LeaveStatus::Approved->value,
                    'reviewer_id' => $reviewerId,
                    'days' => (float) $leaveRequest->days,
                ],
            );

            return $leaveRequest->fresh([
                'user',
                'leaveType',
                'reviewer',
                'decisions.reviewer',
            ]);
        });
    }

    public function reject(LeaveRequest $leaveRequest,int $reviewerId,string $rejectionReason): LeaveRequest {
        return DB::transaction(function () use ($leaveRequest,$reviewerId,$rejectionReason,) 
        {
            $leaveRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);
    
            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw new RuntimeException(
                    __('leave_requests.only_pending_reject')
                );
            }
    
            // Get the reviewer who rejected the leave request.
            $reviewer = User::findOrFail($reviewerId);
    
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
    
            // Record the rejection in the unified audit log.
            $this->auditService->record(
                actor: $reviewer,
                action: AuditAction::LEAVE_REJECTED,
                entity: $leaveRequest,
                metadata: [
                    'old_status' => $previousStatus,
                    'new_status' => LeaveStatus::Rejected->value,
                    'reviewer_id' => $reviewerId,
                    'rejection_reason' => $rejectionReason,
                ],
            );
    
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
