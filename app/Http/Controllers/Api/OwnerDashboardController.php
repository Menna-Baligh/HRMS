<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\OwnerDashboardService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OwnerDashboardController extends Controller
{
    public function __construct(
        protected OwnerDashboardService $ownerDashboardService
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $data = $this->ownerDashboardService->getDashboardData();

            return ResponseHelper::success(
                data: $data,
                message: __('dashboard.owner.fetched_successfully')
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
