<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HrGoalController extends Controller
{
    public function __construct(private GoalService $goalService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $departmentId = $request->query('department_id') ? (int) $request->query('department_id') : null;
            $employeeId = $request->query('employee_id') ? (int) $request->query('employee_id') : null;

            $goals = $this->goalService->getHrGoalsOverview($status, $departmentId, $employeeId);

            return ResponseHelper::success(
                GoalResource::collection($goals)->response()->getData(true),
                'Company goals overview retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve company goals overview.',
                500
            );
        }
    }
}