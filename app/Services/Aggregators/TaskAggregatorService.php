<?php
namespace App\Services\Aggregators;

use App\Models\TaskAssignment;
use Carbon\Carbon;

class TaskAggregatorService
{

    public function getMetrics(int $userId, string $startDate, string $endDate): array
    {
        $assignments = TaskAssignment::with('task')
            ->where('employee_id', $userId)
            ->whereHas('task', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->get();

        $totalTasks = $assignments->count();
        $completedTasks = 0;
        $inProgressTasks = 0;
        $pendingTasks = 0;
        $overdueTasks = 0;
        $totalProgress = 0;

        $now = Carbon::now();

        foreach ($assignments as $assignment) {
            $task = $assignment->task;
            if (! $task) {
                continue;
            }

            $totalProgress += $task->progress ?? 0;

            if ($task->status === 'Completed' || $task->status === 'Closed') {
                $completedTasks++;
            } elseif ($task->status === 'In Progress') {
                $inProgressTasks++;
            } else {
                $pendingTasks++;
            }

            if ($task->status !== 'Completed' && $task->status !== 'Closed') {
                if (Carbon::parse($task->deadline)->isPast()) {
                    $overdueTasks++;
                }
            }
        }

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0.0;

        $averageProgress = $totalTasks > 0
            ? round($totalProgress / $totalTasks, 2)
            : 0.0;

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'pending_tasks' => $pendingTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_rate' => $completionRate,
            'average_progress' => $averageProgress,
        ];
    }
}
