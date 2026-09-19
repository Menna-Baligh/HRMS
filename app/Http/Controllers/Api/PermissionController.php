<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();

            return ResponseHelper::success(
                data: $permissions,
                message: __('permissions.retrieved_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('permissions.failed_to_retrieve'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
