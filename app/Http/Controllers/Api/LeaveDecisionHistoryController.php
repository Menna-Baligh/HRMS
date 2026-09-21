<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveRequests\LeaveRequestService;
use Illuminate\Http\JsonResponse;

class LeaveDecisionHistoryController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {
    }

    public function index(int $leaveRequest): JsonResponse
    {
        $leaveRequestModel = LeaveRequest::findOrFail($leaveRequest);

        $decisions = $this->leaveRequestService->getDecisionHistory(
            leaveRequest: $leaveRequestModel
        );

        return ResponseHelper::success(
            data: $decisions,
            message: __('leave_decisions.retrieved_successfully')
        );
    }
}