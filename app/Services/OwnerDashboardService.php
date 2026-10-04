<?php

namespace App\Services;

use App\Models\CompanyLocation;
use App\Models\Evaluation;
use App\Models\Policy;
use App\Models\PolicyAudit;
use App\Models\User;
use Carbon\Carbon;

class OwnerDashboardService
{
    public function getDashboardData(): array
    {
        return [
            'kpis' => $this->getKpiCards(),
            'recent_activities' => $this->getRecentCriticalActivities(),
            'quick_view_users' => $this->getQuickViewUsers(),
        ];
    }

    private function getKpiCards(): array
    {
        $totalUsers = User::count();

        $branchLocationsCount = CompanyLocation::where('is_active', true)->count();

        $totalEvaluations = Evaluation::count();
        $completedEvaluations = Evaluation::where('status', 'completed')->count();
        $reviewCompletionRate = $totalEvaluations > 0
            ? round(($completedEvaluations / $totalEvaluations) * 100)
            : 0;

        $activePoliciesCount = Policy::where('status', 'active')->count();

        return [
            'total_users' => [
                'label' => __('dashboard.owner.total_users'),
                'value' => $totalUsers,
                'subtext' => __('dashboard.owner.across_all_roles'),
            ],
            'branch_locations' => [
                'label' => __('dashboard.owner.branch_locations'),
                'value' => $branchLocationsCount,
            ],
            'review_completion' => [
                'label' => __('dashboard.owner.review_completion'),
                'value' => $reviewCompletionRate,
                'formatted' => "{$reviewCompletionRate}%",
            ],
            'active_policies' => [
                'label' => __('dashboard.owner.active_policies'),
                'value' => $activePoliciesCount,
            ],
        ];
    }

    private function getRecentCriticalActivities(): array
    {
        return PolicyAudit::with('performedBy:id,name')
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->performedBy->name ?? 'System',
                    'action' => $log->description ?? $log->action ?? __('dashboard.owner.audit_action_default'),
                    'timestamp' => $log->created_at ? Carbon::parse($log->created_at)->diffForHumans() : 'N/A',
                    'severity' => $this->getSeverityByAction($log->action),
                ];
            })
            ->toArray();
    }

    private function getQuickViewUsers(): array
    {
        return User::latest()
            ->take(4)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'job_title' => $user->job_title ?? $user->role ?? 'N/A',
                    'status' => $user->status ?? 'active',
                ];
            })
            ->toArray();
    }

    private function getSeverityByAction(?string $action): string
    {
        if (! $action) {
            return 'Info';
        }

        return match (true) {
            str_contains(strtolower($action), 'delete') || str_contains(strtolower($action), 'deactivate') => 'Warning',
            str_contains(strtolower($action), 'create') || str_contains(strtolower($action), 'update') => 'Success',
            default => 'Info',
        };
    }
}
