<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $user = $this->employeeService->createEmployee($request->validated());

            return ResponseHelper::success(
                data: new EmployeeResource($user),
                message: 'Employee created successfully',
                statusCode: Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to create employee',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
