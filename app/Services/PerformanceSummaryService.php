<?php
namespace App\Services;

use App\DTOs\PerformanceMetricData;
use App\Models\Employee;
use App\Models\EvaluationPeriod;
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
                    'start_date' => Carbon::parse($period->start_date)->toDateString(),
                    'end_date' => Carbon::parse($period->end_date)->toDateString(),
                    'period_name' => $period->name,
                ];
            }
        }

        if ($startDate && $endDate) {
            return [
                'start_date' => Carbon::parse($startDate)->toDateString(),
                'end_date' => Carbon::parse($endDate)->toDateString(),
                'period_name' => 'Custom Period',
            ];
        }

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'period_name' => $start->format('F Y'),
        ];
    }


    public function getEmployeeSummary(Employee $employee, array $dateRange, ?int $periodId = null): array
    {
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];

        $attendance = $this->attendanceAggregator->getMetrics($employee->id, $startDate, $endDate);
        $tasks = $this->taskAggregator->getMetrics($employee->user_id, $startDate, $endDate);
        $goals = $this->goalAggregator->getMetrics($employee->id, $startDate, $endDate);
        $evaluations = $this->evaluationAggregator->getMetrics($employee->id, $periodId, $startDate, $endDate);

        $attendanceRate = $attendance['attendance_rate'] ?? 0;
        $taskRate = $tasks['completion_rate'] ?? 0;

        $qualityRate = min(100, (float) ($evaluations['latest_overall_score'] ?? 0));

        $overallScore = round(($attendanceRate * 0.35) + ($taskRate * 0.40) + ($qualityRate * 0.25), 2);

        $metricData = new PerformanceMetricData($attendance, $tasks, $goals, $evaluations);

        return [
            'period_name' => $dateRange['period_name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'overall_score' => $overallScore,
            'at_a_glance' => [
                'tasks_rate' => $taskRate,
                'quality_rate' => $qualityRate,
                'attendance_rate' => $attendanceRate,
            ],
            'metrics' => $metricData->toArray(),
        ];
    }


    public function getPeriodComparison(Employee $employee, array $currentRange): array
    {
        $currentSummary = $this->getEmployeeSummary($employee, $currentRange);

        $prevStart = Carbon::parse($currentRange['start_date'])->subMonth()->startOfMonth()->toDateString();
        $prevEnd = Carbon::parse($currentRange['start_date'])->subMonth()->endOfMonth()->toDateString();

        $prevRange = [
            'start_date' => $prevStart,
            'end_date' => $prevEnd,
            'period_name' => Carbon::parse($prevStart)->format('F Y'),
        ];

        $previousSummary = $this->getEmployeeSummary($employee, $prevRange);

        $currentOverall = $currentSummary['overall_score'];
        $prevOverall = $previousSummary['overall_score'];

        $overallChange = round($currentOverall - $prevOverall, 2);

        return [
            'current_period' => [
                'name' => $currentSummary['period_name'],
                'overall_score' => $currentOverall,
                'at_a_glance' => $currentSummary['at_a_glance'],
            ],
            'previous_period' => [
                'name' => $previousSummary['period_name'],
                'overall_score' => $prevOverall,
                'at_a_glance' => $previousSummary['at_a_glance'],
            ],
            'comparison' => [
                'overall_change' => $overallChange,
                'trend' => $overallChange >= 0 ? 'up' : 'down',
                'change_label' => ($overallChange >= 0 ? "+{$overallChange}%" : "{$overallChange}%") . ' from last month',
            ],
        ];
    }
    public function getDashboardPerformance(Employee $employee, ?int $periodId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = $this->resolveDateRange($periodId, $startDate, $endDate);

        $summary = $this->getEmployeeSummary($employee, $dateRange, $periodId);

        $comparison = $this->getPeriodComparison($employee, $dateRange);

        $trendChart = $this->getSixMonthTrend($employee, $dateRange['start_date']);

        return [
            'period_name' => $dateRange['period_name'],
            'start_date' => $dateRange['start_date'],
            'end_date' => $dateRange['end_date'],
            'overall' => [
                'score' => $summary['overall_score'],
                'change_label' => $comparison['comparison']['change_label'],
                'trend' => $comparison['comparison']['trend'],
            ],
            'at_a_glance' => $summary['at_a_glance'],
            'performance_trend' => $trendChart,
            'metrics' => $summary['metrics'],
        ];
    }


    private function getSixMonthTrend(Employee $employee, string $currentStartDate): array
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

            $monthSummary = $this->getEmployeeSummary($employee, $range);

            $trend[] = [
                'month' => $monthDate->format('M'),
                'overall_score' => $monthSummary['overall_score'],
            ];
        }

        return $trend;
    }
}
