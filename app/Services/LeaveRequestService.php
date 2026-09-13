<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use App\Events\LeaveRequestSubmitted;
use App\Models\LeaveDecisionHistory;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(
        private readonly LeaveDateCalculator $calculator,
        private readonly LeaveBalanceService $balanceService,
    ) {}

    // ─── Create ───────────────────────────────────────────────────────────────

    /**
     * Submit a new leave request.
     *
     * Validates:
     * 1. Leave type is active.
     * 2. No date overlap with existing active requests.
     * 3. Sufficient balance (if leave type requires it).
     * 4. Attachment provided (if leave type requires it).
     *
     * @param  array{start_date: string, end_date: string, reason: string}  $data
     *
     * @throws \DomainException on business-rule violations.
     */
    public function create(
        User $user,
        LeaveType $leaveType,
        array $data,
        ?UploadedFile $attachment = null,
    ): LeaveRequest {
        if (! $leaveType->is_active) {
            throw new \DomainException("Leave type [{$leaveType->name}] is not active.");
        }

        if ($leaveType->requires_attachment && ! $attachment) {
            throw new \DomainException("An attachment is required for leave type [{$leaveType->name}].");
        }

        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $requestedDays = $this->calculator->calculate($startDate, $endDate);
        $year = (int) date('Y', strtotime($startDate));

        // Overlap check
        $this->assertNoOverlap($user->id, $startDate, $endDate);

        // Balance check (read-only, before entering transaction)
        $this->balanceService->checkSufficient($user, $leaveType, $requestedDays, $year);

        // Store attachment if provided
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store("leave-attachments/{$user->id}", 'local');
        }

        $request = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'status' => LeaveStatus::Pending,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'requested_days' => $requestedDays,
            'reason' => $data['reason'],
            'attachment_path' => $attachmentPath,
        ]);

        event(new LeaveRequestSubmitted($request));

        return $request;
    }

    // ─── Approve by Manager ───────────────────────────────────────────────────

    /**
     * Manager (or Owner) approves a pending request.
     *
     * Transition: pending → approved_by_manager
     */
    public function approveByManager(LeaveRequest $request, User $reviewer, ?string $note = null): LeaveRequest
    {
        if ($request->status !== LeaveStatus::Pending) {
            throw new \DomainException(
                "Cannot approve: request is [{$request->status->value}], expected [pending]."
            );
        }

        DB::transaction(function () use ($request, $reviewer, $note): void {
            $previous = $request->status;

            $request->update([
                'status' => LeaveStatus::ApprovedByManager,
                'manager_id' => $reviewer->id,
            ]);

            $this->recordHistory($request, $reviewer, $previous, LeaveStatus::ApprovedByManager, $note);
        });

        return $request->fresh();
    }

    // ─── Approve by HR ────────────────────────────────────────────────────────

    /**
     * HR (or Owner) gives final approval — balance deducted HERE.
     *
     * Transition: approved_by_manager → approved
     */
    public function approveByHR(LeaveRequest $request, User $reviewer, ?string $note = null): LeaveRequest
    {
        if ($request->status !== LeaveStatus::ApprovedByManager) {
            throw new \DomainException(
                "Cannot approve: request is [{$request->status->value}], expected [approved_by_manager]."
            );
        }

        DB::transaction(function () use ($request, $reviewer, $note): void {
            $previous = $request->status;
            $year = (int) $request->start_date->format('Y');

            // Deduct balance inside transaction (with pessimistic lock)
            $this->balanceService->deduct(
                $request->user,
                $request->leaveType,
                (float) $request->requested_days,
                $year,
            );

            $request->update([
                'status' => LeaveStatus::Approved,
                'hr_id' => $reviewer->id,
            ]);

            $this->recordHistory($request, $reviewer, $previous, LeaveStatus::Approved, $note);
        });

        event(new LeaveRequestApproved($request->fresh()));

        return $request->fresh();
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    /**
     * Manager or HR rejects a request.
     *
     * Allowed from: pending (manager) or approved_by_manager (HR).
     */
    public function reject(
        LeaveRequest $request,
        User $reviewer,
        string $rejectionReason,
        ?string $note = null,
    ): LeaveRequest {
        $allowedStatuses = [LeaveStatus::Pending, LeaveStatus::ApprovedByManager];

        if (! in_array($request->status, $allowedStatuses, true)) {
            throw new \DomainException(
                "Cannot reject: request is [{$request->status->value}]."
            );
        }

        DB::transaction(function () use ($request, $reviewer, $rejectionReason, $note): void {
            $previous = $request->status;

            $request->update([
                'status' => LeaveStatus::Rejected,
                'rejection_reason' => $rejectionReason,
            ]);

            $this->recordHistory($request, $reviewer, $previous, LeaveStatus::Rejected, $note);
        });

        event(new LeaveRequestRejected($request->fresh()));

        return $request->fresh();
    }

    // ─── Cancel ───────────────────────────────────────────────────────────────

    /**
     * Employee cancels their own pending or manager-approved request.
     *
     * Balance is NOT deducted (only approved triggers deduction),
     * so no refund is needed here.
     */
    public function cancel(LeaveRequest $request, User $actor): LeaveRequest
    {
        if (! $request->status->isCancellable()) {
            throw new \DomainException(
                "Cannot cancel: request is [{$request->status->value}]."
            );
        }

        DB::transaction(function () use ($request, $actor): void {
            $previous = $request->status;

            $request->update(['status' => LeaveStatus::Cancelled]);

            $this->recordHistory($request, $actor, $previous, LeaveStatus::Cancelled);
        });

        return $request->fresh();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Throw a DomainException if the user already has an active request overlapping [$start, $end].
     *
     * @throws \DomainException
     */
    private function assertNoOverlap(int $userId, string $start, string $end): void
    {
        $overlap = LeaveRequest::where('user_id', $userId)
            ->active()
            ->overlapping($start, $end)
            ->exists();

        if ($overlap) {
            throw new \DomainException(
                "Date overlap: you already have an active leave request during [{$start}] – [{$end}]."
            );
        }
    }

    /** Append an immutable audit row. */
    private function recordHistory(
        LeaveRequest $request,
        User $reviewer,
        LeaveStatus $previous,
        LeaveStatus $new,
        ?string $note = null,
    ): void {
        LeaveDecisionHistory::create([
            'leave_request_id' => $request->id,
            'reviewer_id' => $reviewer->id,
            'previous_status' => $previous,
            'new_status' => $new,
            'note' => $note,
            'decided_at' => now(),
        ]);
    }
}
