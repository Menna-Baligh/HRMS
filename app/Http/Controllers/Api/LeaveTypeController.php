<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
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
    ) {}

    /**
     * Get all leave types.
     */
    public function index(): JsonResponse
    {
        return ResponseHelper::success(
            $this->leaveTypeService->index(),
            __('leave_types.retrieved_successfully')
        );
    }

    /**
     * Create a new leave type.
     */
    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->leaveTypeService->create(
            $request->validated()
        );

        return ResponseHelper::success(
            $leaveType,
            __('leave_types.created_successfully'),
            201
        );
    }

    /**
     * Update a leave type.
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->update(
            $leaveType,
            $request->validated()
        );

        return ResponseHelper::success(
            $leaveType,
            __('leave_types.updated_successfully')
        );
    }

    /**
     * Activate a leave type.
     */
    public function activate(LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->activate($leaveType);

        return ResponseHelper::success(
            $leaveType,
            __('leave_types.activated_successfully')
        );
    }

    /**
     * Deactivate a leave type.
     */
    public function deactivate(LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->deactivate($leaveType);

        return ResponseHelper::success(
            $leaveType,
            __('leave_types.deactivated_successfully')
        );
    }
}
