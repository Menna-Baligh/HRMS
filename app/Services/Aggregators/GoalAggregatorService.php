<?php
namespace App\Services\Aggregators;

use App\Models\Goal;

class GoalAggregatorService
{

    public function getMetrics(int $employeeId, string $startDate, string $endDate): array
    {
        $goals = Goal::where('employee_id', $employeeId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $totalGoals = $goals->count();
        $completedGoals = $goals->where('status', 'completed')->count();
        $averageProgress = $totalGoals > 0 ? round($goals->avg('progress'), 2) : 0.0;

        $completionRate = $totalGoals > 0
            ? round(($completedGoals / $totalGoals) * 100, 2)
            : 0.0;

        return [
            'total_goals' => $totalGoals,
            'completed_goals' => $completedGoals,
            'completion_rate' => $completionRate,
            'average_progress' => $averageProgress,
        ];
    }
}
