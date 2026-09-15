<?php
namespace App\Services;

use App\Models\Employee;
use App\Services\PerformanceSummaryService;
use Carbon\Carbon;

class ManagerPerformanceService
{
    public function __construct(
        private PerformanceSummaryService $performanceSummaryService
    ) {}


    public function getTeamDashboardPerformance(
        Employee $managerEmployee,
        ?int $periodId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 10
    ): array {
        $dateRange = $this->performanceSummaryService->resolveDateRange($periodId, $startDate, $endDate);

        $allTeamMembers = Employee::with('user')
            ->where('manager_id', $managerEmployee->id)
            ->orWhere('manager_id', $managerEmployee->user_id)
            ->get();

        if ($allTeamMembers->isEmpty()) {
            return [
                'period_name' => $dateRange['period_name'],
                'start_date' => $dateRange['start_date'],
                'end_date' => $dateRange['end_date'],
                'team_summary' => [
                    'overall_score' => 0,
                    'total_members' => 0,
                    'high_performers_count' => 0,
                    'needs_attention_count' => 0,
                ],
                'at_a_glance' => [
                    'tasks_rate' => 0,
                    'quality_rate' => 0,
                    'attendance_rate' => 0,
                ],
                'performance_trend' => [],
                'team_members' => Employee::whereNull('id')->paginate($perPage),
            ];
        }

        $totalOverallScore = 0;
        $totalTasksRate = 0;
        $totalQualityRate = 0;
        $totalAttendanceRate = 0;
        $highPerformersCount = 0;
        $needsAttentionCount = 0;

        foreach ($allTeamMembers as $member) {
            $summary = $this->performanceSummaryService->getEmployeeSummary($member, $dateRange, $periodId);
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

        $totalMembersCount = $allTeamMembers->count();

        $paginatedMembers = Employee::with('user')
            ->where(function ($q) use ($managerEmployee) {
                $q->where('manager_id', $managerEmployee->id)
                ->orWhere('manager_id', $managerEmployee->user_id);
            })
            ->paginate($perPage)
            ->through(function ($member) use ($dateRange, $periodId) {
                $summary = $this->performanceSummaryService->getEmployeeSummary($member, $dateRange, $periodId);
                $score = $summary['overall_score'];
                $atAGlance = $summary['at_a_glance'];

                $statusLabel = 'Good';
                if ($score >= 80) {
                    $statusLabel = 'High Performer';
                } elseif ($score < 70) {
                    $statusLabel = 'Needs Attention';
                }

                return [
                    'employee_id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user->name ?? 'N/A',
                    'job_title' => $member->job_title ?? 'N/A',
                    'overall_score' => $score,
                    'tasks_rate' => $atAGlance['tasks_rate'],
                    'attendance_rate' => $atAGlance['attendance_rate'],
                    'quality_rate' => $atAGlance['quality_rate'],
                    'status_label' => $statusLabel,
                ];
            });

        $trendChart = $this->getTeamSixMonthTrend($allTeamMembers, $dateRange['start_date']);

        return [
            'period_name' => $dateRange['period_name'],
            'start_date' => $dateRange['start_date'],
            'end_date' => $dateRange['end_date'],
            'team_summary' => [
                'overall_score' => round($totalOverallScore / $totalMembersCount, 2),
                'total_members' => $totalMembersCount,
                'high_performers_count' => $highPerformersCount,
                'needs_attention_count' => $needsAttentionCount,
            ],
            'at_a_glance' => [
                'tasks_rate' => round($totalTasksRate / $totalMembersCount, 2),
                'quality_rate' => round($totalQualityRate / $totalMembersCount, 2),
                'attendance_rate' => round($totalAttendanceRate / $totalMembersCount, 2),
            ],
            'performance_trend' => $trendChart,
            'team_members' => $paginatedMembers,
        ];
    }

    private function getTeamSixMonthTrend($teamMembers, string $currentStartDate): array
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
            foreach ($teamMembers as $member) {
                $summary = $this->performanceSummaryService->getEmployeeSummary($member, $range);
                $monthTotalScore += $summary['overall_score'];
            }

            $trend[] = [
                'month' => $monthDate->format('M'),
                'overall_score' => round($monthTotalScore / $teamMembers->count(), 2),
            ];
        }

        return $trend;
    }
}
