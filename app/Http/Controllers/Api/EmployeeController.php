<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    public function show(int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->getEmployeeById($id);
            Gate::authorize('view', $employee);
            return ResponseHelper::success(
                data: new EmployeeResource($employee->user),
                message: 'Employee details retrieved successfully'
            );
        }catch(AuthorizationException $e) {
            return ResponseHelper::error(
                message: 'You are not authorized to view this employee profile.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::error(
                message: 'Employee not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            report($e);
            return ResponseHelper::error(
                message: 'Failed to retrieve employee details',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
