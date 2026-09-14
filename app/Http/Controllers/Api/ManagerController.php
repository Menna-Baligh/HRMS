<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Http\Resources\UserResource;
use App\Services\EmployeeService;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ManagerController extends Controller
{
    public function __construct(private EmployeeService $employeeService, private GoalService $goalService) {}

    public function employees(Request $request): JsonResponse
    {
        $managerEmployeeId = auth('api')->user()?->employee?->id;

        if (! $managerEmployeeId) {
            return ResponseHelper::error(
                message: 'You are not registered as an employee.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }
        $filters = $request->only(['search', 'status', 'department_id', 'employment_type', 'role']);
        $filters['manager_id'] = $managerEmployeeId;
        $perPage = (int) $request->get('per_page', 15);
        $employees = $this->employeeService->getAllEmployees($filters, $perPage);
        $paginatedData = UserResource::collection($employees)->response()->getData(true);

        return ResponseHelper::success(
            data: $paginatedData,
            message: 'Manager employees retrieved successfully.'
        );
    }
    public function teamGoals(Request $request): JsonResponse
    {
        try {
            $manager = $request->user()->employee;

            if (! $manager) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $status = $request->query('status');
            $employeeId = $request->query('employee_id') ? (int) $request->query('employee_id') : null;

            $goals = $this->goalService->getManagerTeamGoals($manager, $status, $employeeId);

            return ResponseHelper::success(
                GoalResource::collection($goals)->response()->getData(true),
                'Team goals retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve team goals.',
                500
            );
        }
    }
}
