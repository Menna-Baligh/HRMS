<?php

namespace App\Services;

use App\DTOs\PerformanceMetricData;
use App\Models\EvaluationPeriod;
use App\Models\User;
use App\Services\Aggregators\AttendanceAggregatorService;
use App\Services\Aggregators\EvaluationAggregatorService;
use App\Services\Aggregators\GoalAggregatorService;
use App\Services\Aggregators\TaskAggregatorService;
use Carbon\Carbon;

class PerformanceSummaryService
{
    public function __construct(
        private AttendanceAggregatorService $attendanceAggregator,
        private TaskAggregatorService $taskAggregator,
        private GoalAggregatorService $goalAggregator,
        private EvaluationAggregatorService $evaluationAggregator
    ) {}

    public function resolveDateRange(?int $periodId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($periodId) {
            $period = EvaluationPeriod::find($periodId);
            if ($period) {
                return [
                    'start_date'  => Carbon::parse($period->start_date)->toDateString(),
                    'end_date'    => Carbon::parse($period->end_date)->toDateString(),
                    'period_name' => $period->name,
                ];
            }
        }

        if ($startDate && $endDate) {
            return [
                'start_date'  => Carbon::parse($startDate)->toDateString(),
                'end_date'    => Carbon::parse($endDate)->toDateString(),
                'period_name' => __('performance.periods.custom_period'),
            ];
        }

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return [
            'start_date'  => $start->toDateString(),
            'end_date'    => $end->toDateString(),
            'period_name' => $start->translatedFormat('F Y'),
        ];
    }

    public function getEmployeeSummary(User $user, array $dateRange, ?int $periodId = null): array
    {
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];

        $attendance = $this->attendanceAggregator->getMetrics($user->id, $startDate, $endDate);
        $tasks = $this->taskAggregator->getMetrics($user->id, $startDate, $endDate);
        $goals = $this->goalAggregator->getMetrics($user->id, $startDate, $endDate);
        $evaluations = $this->evaluationAggregator->getMetrics($user->id, $periodId, $startDate, $endDate);

        $attendanceRate = $attendance['attendance_rate'] ?? 0;
        $taskRate = $tasks['completion_rate'] ?? 0;
        $qualityRate = min(100, (float) ($evaluations['latest_overall_score'] ?? 0));

        $overallScore = round(($attendanceRate * 0.35) + ($taskRate * 0.40) + ($qualityRate * 0.25), 2);

        $metricData = new PerformanceMetricData($attendance, $tasks, $goals, $evaluations);

        return [
            'period_name'   => $dateRange['period_name'],
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'overall_score' => $overallScore,
            'at_a_glance'   => [
                'tasks_rate'      => $taskRate,
                'quality_rate'    => $qualityRate,
                'attendance_rate' => $attendanceRate,
            ],
            'metrics'       => $metricData->toArray(),
        ];
    }

    public function getPeriodComparison(User $user, array $currentRange): array
    {
        $currentSummary = $this->getEmployeeSummary($user, $currentRange);

        $prevStart = Carbon::parse($currentRange['start_date'])->subMonth()->startOfMonth()->toDateString();
        $prevEnd = Carbon::parse($currentRange['start_date'])->subMonth()->endOfMonth()->toDateString();

        $prevRange = [
            'start_date'  => $prevStart,
            'end_date'    => $prevEnd,
            'period_name' => Carbon::parse($prevStart)->translatedFormat('F Y'),
        ];

        $previousSummary = $this->getEmployeeSummary($user, $prevRange);

        $currentOverall = $currentSummary['overall_score'];
        $prevOverall = $previousSummary['overall_score'];

        $overallChange = round($currentOverall - $prevOverall, 2);

        return [
            'current_period' => [
                'name'          => $currentSummary['period_name'],
                'overall_score' => $currentOverall,
                'at_a_glance'   => $currentSummary['at_a_glance'],
            ],
            'previous_period' => [
                'name'          => $previousSummary['period_name'],
                'overall_score' => $prevOverall,
                'at_a_glance'   => $previousSummary['at_a_glance'],
            ],
            'comparison' => [
                'overall_change' => $overallChange,
                'trend'          => $overallChange >= 0 ? 'up' : 'down',
                'change_label'   => ($overallChange >= 0 ? "+{$overallChange}%" : "{$overallChange}%").' '.__('performance.periods.from_last_month'),
            ],
        ];
    }

    public function getDashboardPerformance(User $user, ?int $periodId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = $this->resolveDateRange($periodId, $startDate, $endDate);

        $summary = $this->getEmployeeSummary($user, $dateRange, $periodId);
        $comparison = $this->getPeriodComparison($user, $dateRange);
        $trendChart = $this->getSixMonthTrend($user, $dateRange['start_date']);

        return [
            'period_name'       => $dateRange['period_name'],
            'start_date'        => $dateRange['start_date'],
            'end_date'          => $dateRange['end_date'],
            'overall'           => [
                'score'        => $summary['overall_score'],
                'change_label' => $comparison['comparison']['change_label'],
                'trend'        => $comparison['comparison']['trend'],
            ],
            'at_a_glance'       => $summary['at_a_glance'],
            'performance_trend' => $trendChart,
            'metrics'           => $summary['metrics'],
        ];
    }

    private function getSixMonthTrend(User $user, string $currentStartDate): array
    {
        $trend = [];
        $baseDate = Carbon::parse($currentStartDate);

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $baseDate->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth()->toDateString();
            $monthEnd = $monthDate->copy()->endOfMonth()->toDateString();

            $range = [
                'start_date'  => $monthStart,
                'end_date'    => $monthEnd,
                'period_name' => $monthDate->translatedFormat('M'),
            ];

            $monthSummary = $this->getEmployeeSummary($user, $range);

            $trend[] = [
                'month'         => $monthDate->translatedFormat('M'),
                'overall_score' => $monthSummary['overall_score'],
            ];
        }

        return $trend;
    }
}
