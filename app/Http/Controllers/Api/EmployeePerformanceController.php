<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\PerformanceSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EmployeePerformanceController extends Controller
{
    public function __construct(private PerformanceSummaryService $summaryService) {}

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('performance.user_not_found'), 404);
            }

            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $dashboardData = $this->summaryService->getDashboardPerformance(
                $user,
                $periodId,
                $startDate,
                $endDate
            );

            return ResponseHelper::success(
                $dashboardData,
                __('performance.employee_dashboard_success')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('performance.failed_employee_dashboard'),
                500
            );
        }
    }
}
