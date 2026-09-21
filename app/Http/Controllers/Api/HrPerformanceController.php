<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\HrCompanyPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HrPerformanceController extends Controller
{
    public function __construct(private HrCompanyPerformanceService $hrService) {}

    public function companyDashboard(Request $request): JsonResponse
    {
        try {
            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $perPage = (int) $request->query('per_page', 10);

            $data = $this->hrService->getCompanyDashboardPerformance(
                $periodId,
                $startDate,
                $endDate,
                $perPage
            );

            return ResponseHelper::success(
                $data,
                __('performance.company_dashboard_success')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('performance.failed_company_dashboard'),
                500
            );
        }
    }
}
