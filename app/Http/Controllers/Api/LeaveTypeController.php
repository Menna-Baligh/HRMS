<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypes\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveTypes\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\LeaveTypes\LeaveTypeService;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    public function __construct(
        protected LeaveTypeService $leaveTypeService
    ) {
    }

    /**
     * Get all leave types.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Leave types retrieved successfully.',
            'data' => $this->leaveTypeService->index(),
        ]);
    }

    /**
     * Create a new leave type.
     */
    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->leaveTypeService->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Leave type created successfully.',
            'data' => $leaveType,
        ], 201);
    }

    /**
     * Update a leave type.
     */
    public function update(UpdateLeaveTypeRequest $request,LeaveType $leaveType): JsonResponse {
        $leaveType = $this->leaveTypeService->update(
            $leaveType,
            $request->validated()
        );

        return response()->json([
            'message' => 'Leave type updated successfully.',
            'data' => $leaveType,
        ]);
    }

    /**
     * Activate a leave type.
     */
    public function activate(LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->activate($leaveType);

        return response()->json([
            'message' => 'Leave type activated successfully.',
            'data' => $leaveType,
        ]);
    }

    /**
     * Deactivate a leave type.
     */
    public function deactivate(LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->deactivate($leaveType);

        return response()->json([
            'message' => 'Leave type deactivated successfully.',
            'data' => $leaveType,
        ]);
    }
}