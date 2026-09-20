<?php

namespace App\Services\Aggregators;

use App\Models\Goal;

class GoalAggregatorService
{
    public function getMetrics(int $userId, string $startDate, string $endDate): array
    {
        $goals = Goal::where('user_id', $userId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('target_date', [$startDate, $endDate])
                    ->orWhereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
            })
            ->get();

        $totalGoals = $goals->count();
        $completedGoals = $goals->where('status', 'completed')->count();

        $totalProgressPercentage = 0;

        foreach ($goals as $goal) {
            if ($goal->target_value > 0) {
                $goalProgress = ($goal->current_value / $goal->target_value) * 100;
                $totalProgressPercentage += min(100, $goalProgress);
            }
        }

        $averageProgress = $totalGoals > 0
            ? round($totalProgressPercentage / $totalGoals, 2)
            : 0.0;

        $completionRate = $totalGoals > 0
            ? round(($completedGoals / $totalGoals) * 100, 2)
            : 0.0;

        return [
            'total_goals'      => $totalGoals,
            'completed_goals'  => $completedGoals,
            'completion_rate'  => $completionRate,
            'average_progress' => $averageProgress,
        ];
    }
}
