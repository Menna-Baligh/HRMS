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
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $dashboardData = $this->summaryService->getDashboardPerformance(
                $employee,
                $periodId,
                $startDate,
                $endDate
            );

            return ResponseHelper::success(
                $dashboardData,
                'Employee performance dashboard retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve performance dashboard.',
                500
            );
        }
    }
}
