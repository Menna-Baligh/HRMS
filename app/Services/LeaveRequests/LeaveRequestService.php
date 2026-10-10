<?php

namespace App\Services\LeaveRequests;

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Jobs\SendNotificationJob;
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
        $leaveRequest = DB::transaction(function () use ($user, $data) {
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
    
            // Create balance if it does not exist.
            $this->leaveBalanceService->createBalancesForUser(
                user: $user,
                year: $startDate->year
            );
    
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
    
            return $leaveRequest->fresh([
                'user',
                'leaveType',
            ]);
        });
    
        /*
         * Notify the employee's manager after the leave request
         * has been successfully created and the transaction has committed.
         */
        $manager = User::find($user->manager_id);
    
       // Notify employee's manager.
            if ($user->manager_id) {
                $manager = User::find($user->manager_id);

                if ($manager) {
                    SendNotificationJob::dispatch(
                        user: $manager,
                        type: 'leave_request_created',
                        titleKey: 'notifications.leave_request_created_title',
                        bodyKey: 'notifications.leave_request_created_body',
                        parameters: [
                            'employee_name' => $user->name,
                            'leave_type' => $this->translateLeaveTypeName(
                                $leaveRequest->leaveType->name,
                                $manager->locale ?: 'ar'
                            ),                        ],
                        metadata: [
                            'screen' => 'leave_request_review',
                            'leave_request_id' => $leaveRequest->id,
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]
                    );
                }
            }

            // Notify all HR users.
            $hrUsers = User::role('HR', 'api')->get();

            foreach ($hrUsers as $hr) {
                SendNotificationJob::dispatch(
                    user: $hr,
                    type: 'leave_request_created',
                    titleKey: 'notifications.leave_request_created_title',
                    bodyKey: 'notifications.leave_request_created_body',
                    parameters: [
                        'employee_name' => $user->name,
                        'leave_type' => $this->translateLeaveTypeName(
                            $leaveRequest->leaveType->name,
                            $manager->locale ?: 'ar'
                        ),                    ],
                    metadata: [
                        'screen' => 'leave_request_review',
                        'leave_request_id' => $leaveRequest->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            }

            // Notify all Owner users.
            $owners = User::role('Owner', 'api')->get();

            foreach ($owners as $owner) {
                SendNotificationJob::dispatch(
                    user: $owner,
                    type: 'leave_request_created',
                    titleKey: 'notifications.leave_request_created_title',
                    bodyKey: 'notifications.leave_request_created_body',
                    parameters: [
                        'employee_name' => $user->name,
                        'leave_type' => $this->translateLeaveTypeName(
                            $leaveRequest->leaveType->name,
                            $manager->locale ?: 'ar'
                        ),                    ],
                    metadata: [
                        'screen' => 'leave_request_review',
                        'leave_request_id' => $leaveRequest->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            }

            return $leaveRequest;
    }

public function approve(
    LeaveRequest $leaveRequest,
    int $reviewerId
): LeaveRequest {
    $leaveRequest = DB::transaction(function () use (
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

        // Deduct the approved leave days from the employee's balance.
        $this->leaveBalanceService->deductBalance(
            user: $user,
            leaveType: $leaveType,
            days: (float) $leaveRequest->days,
            year: $leaveRequest->start_date->year
        );

        // Update the leave request status.
        $leaveRequest->update([
            'status' => LeaveStatus::Approved->value,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        // Store the approval decision.
        LeaveDecision::create([
            'leave_request_id' => $leaveRequest->id,
            'reviewer_id' => $reviewerId,
            'previous_status' => $previousStatus,
            'decision' => LeaveStatus::Approved->value,
            'reason' => null,
            'decided_at' => now(),
        ]);

        // Record the approval in the audit log.
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

    // Notify the employee only after the approval transaction commits.
    SendNotificationJob::dispatch(
        user: $leaveRequest->user,
        type: 'leave_request_approved',
        titleKey: 'notifications.leave_request_approved_title',
        bodyKey: 'notifications.leave_request_approved_body',
        parameters: [
            'leave_type' => $this->translateLeaveTypeName(
                $leaveRequest->leaveType->name,
                $leaveRequest->user->locale ?: 'ar'
            ),
            'start_date' => $leaveRequest->start_date->toDateString(),
            'end_date' => $leaveRequest->end_date->toDateString(),
        ],
        metadata: [
            'screen' => 'leave_request_details',
            'leave_request_id' => $leaveRequest->id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]
    );

    return $leaveRequest;
}

public function reject(
    LeaveRequest $leaveRequest,
    int $reviewerId,
    string $rejectionReason
): LeaveRequest {
    $leaveRequest = DB::transaction(function () use (
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

        // Get the reviewer who rejected the leave request.
        $reviewer = User::findOrFail($reviewerId);
        $previousStatus = $leaveRequest->status->value;

        $leaveRequest->update([
            'status' => LeaveStatus::Rejected->value,
            'rejection_reason' => $rejectionReason,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        // Store the rejection decision.
        LeaveDecision::create([
            'leave_request_id' => $leaveRequest->id,
            'reviewer_id' => $reviewerId,
            'previous_status' => $previousStatus,
            'decision' => LeaveStatus::Rejected->value,
            'reason' => $rejectionReason,
            'decided_at' => now(),
        ]);

        // Record the rejection in the audit log.
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

    // Notify the employee only after the rejection transaction commits.
    SendNotificationJob::dispatch(
        user: $leaveRequest->user,
        type: 'leave_request_rejected',
        titleKey: 'notifications.leave_request_rejected_title',
        bodyKey: 'notifications.leave_request_rejected_body',
        parameters: [
            'leave_type' => $this->translateLeaveTypeName(
                $leaveRequest->leaveType->name,
                $leaveRequest->user->locale ?: 'ar'
            ),
            'start_date' => $leaveRequest->start_date->toDateString(),
            'end_date' => $leaveRequest->end_date->toDateString(),
            'reason' => $this->translateRejectionReason(
                $rejectionReason,
                $leaveRequest->user->locale ?: 'ar'
            ),
        ],
        metadata: [
            'screen' => 'leave_request_details',
            'leave_request_id' => $leaveRequest->id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]
    );

    return $leaveRequest;
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


    private function translateRejectionReason(
        string $reason,
        string $locale
    ): string {
        $normalizedReason = strtolower(trim($reason));
    
        $reasonKey = match ($normalizedReason) {
            'you dont have leave balance',
            "you don't have leave balance",
            'no_leave_balance' => 'no_leave_balance',
    
            default => null,
        };
    
        if ($reasonKey === null) {
            return $reason;
        }
    
        return __("notifications.leave_rejection_reasons.{$reasonKey}", [], $locale);
    }

    /**
 * Translate predefined leave type names based on the recipient's locale.
 */
private function translateLeaveTypeName(
    string $name,
    string $locale
): string {
    $normalizedName = strtolower(trim($name));

    $translationKey = match ($normalizedName) {
        'annual leave' => 'annual_leave',
        'sick leave' => 'sick_leave',
        'casual leave' => 'casual_leave',
        'unpaid leave' => 'unpaid_leave',
        default => null,
    };

    if ($translationKey === null) {
        return $name;
    }

    return __("leave_types.names.{$translationKey}", [], $locale);
}
}
