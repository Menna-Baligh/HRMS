<?php

namespace App\Http\Controllers\Api\V1\LeaveRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\ApproveLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\RejectLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    /**
     * Manager approves a pending request.
     */
    public function approveByManager(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->loadMissing(['user', 'leaveType']);

        Gate::authorize('approveByManager', $leaveRequest);

        try {
            $updated = $this->leaveRequestService->approveByManager(
                $leaveRequest,
                $request->user(),
                $request->input('note'),
            );

            return response()->json([
                'message' => 'Leave request approved by manager.',
                'data' => new LeaveRequestResource($updated->load(['user', 'leaveType', 'manager', 'decisionHistories.reviewer'])),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * HR gives final approval (balance is deducted here).
     */
    public function approveByHR(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->loadMissing(['user', 'leaveType']);

        Gate::authorize('approveByHR', $leaveRequest);

        try {
            $updated = $this->leaveRequestService->approveByHR(
                $leaveRequest,
                $request->user(),
                $request->input('note'),
            );

            return response()->json([
                'message' => 'Leave request finally approved by HR.',
                'data' => new LeaveRequestResource($updated->load(['user', 'leaveType', 'manager', 'hr', 'decisionHistories.reviewer'])),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Reject a leave request (Manager from pending, or HR from approved_by_manager).
     */
    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->loadMissing(['user', 'leaveType']);

        Gate::authorize('reject', $leaveRequest);

        try {
            $updated = $this->leaveRequestService->reject(
                $leaveRequest,
                $request->user(),
                $request->validated('rejection_reason'),
                $request->input('note'),
            );

            return response()->json([
                'message' => 'Leave request rejected.',
                'data' => new LeaveRequestResource($updated->load(['user', 'leaveType', 'manager', 'hr', 'decisionHistories.reviewer'])),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
