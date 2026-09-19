<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveBalances\IndexLeaveBalanceRequest;
use App\Services\LeaveBalances\LeaveBalanceService;
use Illuminate\Http\JsonResponse;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $leaveBalanceService
    ) {
    }

    /**
     * Get leave balances for the authenticated employee.
     */
    public function index(IndexLeaveBalanceRequest $request): JsonResponse {
        
        $user = $request->user();
        /*
         * Assuming the authenticated User has a one-to-one
         * relationship with the Employee model.
         */
        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found.',
            ], 404);
        }
        $balances = $this->leaveBalanceService->getMyBalances(
            employee: $employee,
            year: $request->validated('year'),
            leaveTypeId: $request->validated('leave_type_id')
        );
        return response()->json([
            'message' => 'Leave balances retrieved successfully.',
            'data' => $balances,
        ]);
    }
}