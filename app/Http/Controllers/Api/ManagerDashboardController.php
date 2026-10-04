<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ManagerDashboardController extends Controller
{
    public function __construct(
        protected ManagerDashboardService $dashboardService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getDashboardData($request->user());

            return ResponseHelper::success(
                data: $data,
                message: __('dashboard.manager.fetched_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: config('app.debug') ? $e->getMessage() : __('dashboard.failed_to_fetch'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
