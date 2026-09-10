<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerController extends Controller
{
    public function __construct(private EmployeeService $employeeService) {}

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
}
