<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveBalances\IndexLeaveBalanceRequest;
use App\Services\LeaveBalances\LeaveBalanceService;
use Illuminate\Http\JsonResponse;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService
    ) {}

    /**
     * Get leave balances for the authenticated user.
     */
    public function index(IndexLeaveBalanceRequest $request): JsonResponse
    {
        $user = $request->user();

        $balances = $this->leaveBalanceService->getMyBalances(
            user: $user,
            year: $request->validated('year'),
            leaveTypeId: $request->validated('leave_type_id')
        );

        return ResponseHelper::success(
            $balances,
            __('leave_balances.retrieved_successfully')
        );
    }
}
