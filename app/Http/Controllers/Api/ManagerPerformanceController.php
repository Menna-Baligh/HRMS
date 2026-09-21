<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\ManagerPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ManagerPerformanceController extends Controller
{
    public function __construct(private ManagerPerformanceService $managerService) {}

    public function teamDashboard(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();

            if (! $manager) {
                return ResponseHelper::error(null, __('performance.manager_not_found'), 404);
            }

            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $perPage = (int) $request->query('per_page', 10);

            $data = $this->managerService->getTeamDashboardPerformance(
                $manager,
                $periodId,
                $startDate,
                $endDate,
                $perPage
            );

            return ResponseHelper::success(
                $data,
                __('performance.team_dashboard_success')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('performance.failed_team_dashboard'),
                500
            );
        }
    }
}
