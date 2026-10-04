<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\HrDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HrDashboardController extends Controller
{
    public function __construct(
        protected HrDashboardService $dashboardService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $data = $this->dashboardService->getDashboardData($perPage);

            return ResponseHelper::success(
                data: $data,
                message: __('dashboard.hr.fetched_successfully')
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
