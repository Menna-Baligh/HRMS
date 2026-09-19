<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\RejectLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\StoreLeaveRequest;
use App\Services\LeaveRequests\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {
    }

    /**
     * Create a new leave request.
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $leaveRequest = $this->leaveRequestService->create(
                $employee,
                $request->validated()
            );

            return response()->json([
                'message' => 'Leave request created successfully.',
                'data' => $leaveRequest,
            ], 201);

        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve a pending leave request.
     */
    public function approve( Request $request, int $leaveRequest): JsonResponse {
        try {
            $leaveRequest = $this->leaveRequestService->approve(
                \App\Models\LeaveRequest::findOrFail($leaveRequest),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Leave request approved successfully.',
                'data' => $leaveRequest,
            ], 200);

        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a pending leave request.
     */
    public function reject( RejectLeaveRequestRequest $request,int $leaveRequest): JsonResponse {
        try {
            $leaveRequest = $this->leaveRequestService->reject(
                \App\Models\LeaveRequest::findOrFail($leaveRequest),
                $request->user()->id,
                $request->validated()['rejection_reason']
            );

            return response()->json([
                'message' => 'Leave request rejected successfully.',
                'data' => $leaveRequest,
            ], 200);

        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**

* Get the authenticated employee's leave history.
  */
  public function history(Request $request): JsonResponse
  {
  $employee = $request->user()->employee;

  if (!$employee) {
  return response()->json([
  'message' => 'Employee profile not found.',
  ], 404);
  }

  $leaveRequests = $this->leaveRequestService->getEmployeeHistory(
  $employee,
  $request->query('status'),
  $request->query('leave_type_id')
  ? (int) $request->query('leave_type_id')
  : null,
  $request->query('year')
  ? (int) $request->query('year')
  : null
  );

  return response()->json([
  'message' => 'Leave history retrieved successfully.',
  'data' => $leaveRequests,
  ]);
  }

}
