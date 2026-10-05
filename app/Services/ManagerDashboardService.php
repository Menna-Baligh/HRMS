<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

class ManagerDashboardService
{
    public function getDashboardData(User $manager): array
    {
        $teamUserIds = User::where('manager_id', $manager->id)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        return [
            'kpis' => $this->getKpiCards($teamUserIds),
            'widgets' => [
                'workload_alert' => $this->getTeamWorkloadAlert($teamUserIds),
                'pending_action_queue' => $this->getPendingActionQueue($teamUserIds),
            ],
        ];
    }

    private function getKpiCards(array $teamUserIds): array
    {
        $today = Carbon::today();
        $totalDirectReports = count($teamUserIds);

        $activeTasksCount = Task::whereHas('assignedUsers', function ($q) use ($teamUserIds) {
            $q->whereIn('user_id', $teamUserIds);
        })
            ->whereIn('status', ['Pending', 'In Progress'])
            ->count();

        $pendingSubmissionsCount = Submission::whereIn('user_id', $teamUserIds)
            ->whereIn('status', ['pending', 'Pending Review'])
            ->count();

        $presentCount = Attendance::whereIn('user_id', $teamUserIds)
            ->whereDate('date', $today)
            ->whereIn('status', ['Present', 'Late'])
            ->count();

        $lateCount = Attendance::whereIn('user_id', $teamUserIds)
            ->whereDate('date', $today)
            ->where('status', 'Late')
            ->count();

        return [
            'direct_reports' => [
                'label' => __('dashboard.manager.direct_reports'),
                'value' => $totalDirectReports,
            ],
            'active_tasks' => [
                'label' => __('dashboard.manager.active_tasks'),
                'value' => $activeTasksCount,
                'subtext' => __('dashboard.manager.across_team', ['count' => $totalDirectReports]),
            ],
            'pending_submissions' => [
                'label' => __('dashboard.manager.pending_submissions'),
                'value' => $pendingSubmissionsCount,
                'subtext' => __('dashboard.manager.require_review'),
            ],
            'attendance_today' => [
                'label' => __('dashboard.manager.attendance_today'),
                'count' => $presentCount,
                'total' => $totalDirectReports,
                'formatted' => "{$presentCount}/{$totalDirectReports}",
                'late_count' => $lateCount,
                'subtext' => __('dashboard.manager.late_checkins', ['count' => $lateCount]),
            ],
        ];
    }

    private function getTeamWorkloadAlert(array $teamUserIds): array
    {
        if (empty($teamUserIds)) {
            return [
                'has_alert' => false,
                'title' => __('dashboard.manager.workload_normal'),
                'description' => __('dashboard.manager.no_team_members'),
            ];
        }

        $activeTasks = Task::whereHas('assignedUsers', function ($q) use ($teamUserIds) {
            $q->whereIn('user_id', $teamUserIds);
        })
            ->whereIn('status', ['Pending', 'In Progress'])
            ->with('assignedUsers:id,name')
            ->get();

        $totalActiveTasks = $activeTasks->count();

        if ($totalActiveTasks === 0) {
            return [
                'has_alert' => false,
                'type' => 'WORKLOAD STATUS',
                'title' => __('dashboard.manager.workload_balanced'),
                'description' => __('dashboard.manager.workload_balanced_desc'),
                'action' => null,
            ];
        }

        $userTaskCounts = [];
        foreach ($activeTasks as $task) {
            foreach ($task->assignedUsers as $user) {
                if (in_array($user->id, $teamUserIds)) {
                    $userTaskCounts[$user->id] = ($userTaskCounts[$user->id] ?? 0) + 1;
                }
            }
        }

        arsort($userTaskCounts);
        $topTwoUserIds = array_slice(array_keys($userTaskCounts), 0, 2);
        $topTwoTasksCount = array_sum(array_slice($userTaskCounts, 0, 2));

        $percentage = round(($topTwoTasksCount / $totalActiveTasks) * 100);

        if ($percentage >= 50 && ! empty($topTwoUserIds)) {
            $names = User::whereIn('id', $topTwoUserIds)->pluck('name')->implode(' '.__('dashboard.and').' ');

            $translationKey = count($topTwoUserIds) > 1
                ? 'dashboard.manager.workload_summary_plural'
                : 'dashboard.manager.workload_summary_singular';

            return [
                'has_alert' => true,
                'type' => 'AI WORKLOAD ALERT',
                'title' => __('dashboard.manager.sprint_overload_detected'),
                'description' => __($translationKey, [
                    'names' => $names ?: __('dashboard.manager.top_members'),
                    'percentage' => $percentage,
                ]),
                'action' => __('dashboard.manager.rebalance_tasks'),
            ];
        }

        return [
            'has_alert' => false,
            'type' => 'WORKLOAD STATUS',
            'title' => __('dashboard.manager.workload_balanced'),
            'description' => __('dashboard.manager.workload_balanced_desc'),
            'action' => null,
        ];
    }

    private function getPendingActionQueue(array $teamUserIds): array
    {
        return Submission::whereIn('user_id', $teamUserIds)
            ->whereIn('status', ['pending', 'Pending Review'])
            ->with(['user:id,name', 'task:id,title'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'title' => $submission->task->title ?? __('dashboard.manager.submission_task'),
                    'submitted_by' => $submission->user->name ?? 'N/A',
                    'created_at' => $submission->created_at ? $submission->created_at->diffForHumans() : 'N/A',
                    'action_label' => __('dashboard.manager.review'),
                ];
            })
            ->toArray();
    }
}
