<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;

class HrCompanyPerformanceService
{
    public function __construct(
        private PerformanceSummaryService $performanceSummaryService
    ) {}

    public function getCompanyDashboardPerformance(
        ?int $periodId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 10
    ): array {
        $dateRange = $this->performanceSummaryService->resolveDateRange($periodId, $startDate, $endDate);

        $allEmployees = Employee::with(['user', 'department'])->get();

        if ($allEmployees->isEmpty()) {
            return [
                'period_name' => $dateRange['period_name'],
                'start_date' => $dateRange['start_date'],
                'end_date' => $dateRange['end_date'],
                'company_summary' => [
                    'overall_score' => 0,
                    'total_employees' => 0,
                    'total_departments' => Department::count(),
                    'high_performers_count' => 0,
                    'needs_attention_count' => 0,
                ],
                'at_a_glance' => [
                    'tasks_rate' => 0,
                    'quality_rate' => 0,
                    'attendance_rate' => 0,
                ],
                'performance_trend' => [],
                'departments_performance' => Department::paginate($perPage),
            ];
        }

        $totalOverallScore = 0;
        $totalTasksRate = 0;
        $totalQualityRate = 0;
        $totalAttendanceRate = 0;
        $highPerformersCount = 0;
        $needsAttentionCount = 0;

        foreach ($allEmployees as $employee) {
            $summary = $this->performanceSummaryService->getEmployeeSummary($employee, $dateRange, $periodId);
            $score = $summary['overall_score'];
            $atAGlance = $summary['at_a_glance'];

            $totalOverallScore += $score;
            $totalTasksRate += $atAGlance['tasks_rate'];
            $totalQualityRate += $atAGlance['quality_rate'];
            $totalAttendanceRate += $atAGlance['attendance_rate'];

            if ($score >= 80) {
                $highPerformersCount++;
            } elseif ($score < 70) {
                $needsAttentionCount++;
            }
        }

        $totalCount = $allEmployees->count();

        $paginatedDepartments = Department::paginate($perPage)->through(function ($department) use ($dateRange, $periodId) {
            $deptEmployees = Employee::where('department_id', $department->id)->get();

            if ($deptEmployees->isEmpty()) {
                return [
                    'department_id' => $department->id,
                    'name' => $department->name,
                    'total_employees' => 0,
                    'overall_score' => 0,
                    'tasks_rate' => 0,
                    'attendance_rate' => 0,
                    'quality_rate' => 0,
                    'status_label' => 'No Data',
                ];
            }

            $deptScoreSum = 0;
            $deptTasksSum = 0;
            $deptAttendanceSum = 0;
            $deptQualitySum = 0;

            foreach ($deptEmployees as $emp) {
                $empSummary = $this->performanceSummaryService->getEmployeeSummary($emp, $dateRange, $periodId);
                $deptScoreSum += $empSummary['overall_score'];
                $deptTasksSum += $empSummary['at_a_glance']['tasks_rate'];
                $deptAttendanceSum += $empSummary['at_a_glance']['attendance_rate'];
                $deptQualitySum += $empSummary['at_a_glance']['quality_rate'];
            }

            $empCount = $deptEmployees->count();
            $avgScore = round($deptScoreSum / $empCount, 2);

            $statusLabel = 'Good';
            if ($avgScore >= 80) {
                $statusLabel = 'High Performing Dept';
            } elseif ($avgScore < 70) {
                $statusLabel = 'Needs Attention';
            }

            return [
                'department_id' => $department->id,
                'name' => $department->name,
                'total_employees' => $empCount,
                'overall_score' => $avgScore,
                'tasks_rate' => round($deptTasksSum / $empCount, 2),
                'attendance_rate' => round($deptAttendanceSum / $empCount, 2),
                'quality_rate' => round($deptQualitySum / $empCount, 2),
                'status_label' => $statusLabel,
            ];
        });

        $trendChart = $this->getCompanySixMonthTrend($allEmployees, $dateRange['start_date']);

        return [
            'period_name' => $dateRange['period_name'],
            'start_date' => $dateRange['start_date'],
            'end_date' => $dateRange['end_date'],
            'company_summary' => [
                'overall_score' => round($totalOverallScore / $totalCount, 2),
                'total_employees' => $totalCount,
                'total_departments' => Department::count(),
                'high_performers_count' => $highPerformersCount,
                'needs_attention_count' => $needsAttentionCount,
            ],
            'at_a_glance' => [
                'tasks_rate' => round($totalTasksRate / $totalCount, 2),
                'quality_rate' => round($totalQualityRate / $totalCount, 2),
                'attendance_rate' => round($totalAttendanceRate / $totalCount, 2),
            ],
            'performance_trend' => $trendChart,
            'departments_performance' => $paginatedDepartments,
        ];
    }

    private function getCompanySixMonthTrend($allEmployees, string $currentStartDate): array
    {
        $trend = [];
        $baseDate = Carbon::parse($currentStartDate);

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $baseDate->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth()->toDateString();
            $monthEnd = $monthDate->copy()->endOfMonth()->toDateString();

            $range = [
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
                'period_name' => $monthDate->format('M'),
            ];

            $monthTotalScore = 0;
            foreach ($allEmployees as $emp) {
                $summary = $this->performanceSummaryService->getEmployeeSummary($emp, $range);
                $monthTotalScore += $summary['overall_score'];
            }

            $trend[] = [
                'month' => $monthDate->format('M'),
                'overall_score' => round($monthTotalScore / $allEmployees->count(), 2),
            ];
        }

        return $trend;
    }
}
