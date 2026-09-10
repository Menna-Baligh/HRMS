<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentService $departmentService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $perPage = (int) $request->get('per_page', 15);
        $departments = $this->departmentService->getAllDepartments($filters, $perPage);
        $paginatedData = DepartmentResource::collection($departments)->response()->getData(true);

        return ResponseHelper::success(
            data: $paginatedData,
            message: 'Departments retrieved successfully'
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->createDepartment($request->validated());

        return ResponseHelper::success(
            data: new DepartmentResource($department),
            message: 'Department created successfully',
            statusCode: Response::HTTP_CREATED
        );
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = $this->departmentService->updateDepartment($id, $request->validated());

        return ResponseHelper::success(
            data: new DepartmentResource($department),
            message: 'Department updated successfully'
        );
    }

    public function changeStatus(int $id): JsonResponse
    {
        $department = $this->departmentService->changeDepartmentStatus($id);

        $message = $department->status === 'active'
            ? 'Activated department successfully with keeping employee records.'
            : 'Deactivated department successfully with keeping employee records.';

        return ResponseHelper::success(
            data: new DepartmentResource($department),
            message: $message
        );
    }
}
