<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Evaluation;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SalaryAdvance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class HrDashboardService
{
    public function getDashboardData(int $perPage = 10): array
    {
        return [
            'kpis' => $this->getKpiCards(),
            'widgets' => [
                'operational_readiness' => $this->getOperationalReadiness(),
                'employee_attention_signals' => $this->getAttentionSignals($perPage),
            ],
        ];
    }

    private function getKpiCards(): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $totalActiveEmployees = User::role(['Employee', 'Manager', 'HR'])
            ->where('status', 'active')
            ->count();

        $presentTodayCount = Attendance::whereDate('date', $today)
            ->whereIn('status', ['Present', 'Late'])
            ->count();

        $attendancePercentage = $totalActiveEmployees > 0
            ? round(($presentTodayCount / $totalActiveEmployees) * 100, 1)
            : 0;

        $pendingLeavesCount = LeaveRequest::where('status', 'pending')->count();
        $pendingAdvancesCount = SalaryAdvance::where('status', 'pending')->count();

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $targetMonth = Carbon::now()->format('Y-m');

        $totalBaseSalaries = User::role(['Employee', 'Manager', 'HR'])
            ->where('status', 'active')
            ->sum('salary');

        $totalBonuses = Bonus::where('target_month', $targetMonth)
            ->where('status', 'approved')
            ->sum('amount');

        $totalDeductions = Deduction::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereIn('status', ['deducted', 'queued'])
            ->sum('amount');

        $totalInstallments = SalaryAdvance::where('status', 'approved')
            ->sum('monthly_deduction');

        $projectedPayroll = ($totalBaseSalaries + $totalBonuses) - ($totalDeductions + $totalInstallments);

        return [
            'total_employees' => [
                'label' => __('dashboard.hr.total_employees'),
                'value' => $totalActiveEmployees,
            ],
            'present_today' => [
                'label' => __('dashboard.hr.present_today'),
                'count' => $presentTodayCount,
                'percentage' => $attendancePercentage,
                'formatted' => "{$presentTodayCount}/{$totalActiveEmployees} ({$attendancePercentage}%)",
            ],
            'pending_reviews' => [
                'label' => __('dashboard.hr.pending_reviews'),
                'total' => $pendingLeavesCount + $pendingAdvancesCount,
                'leaves_count' => $pendingLeavesCount,
                'advances_count' => $pendingAdvancesCount,
                'formatted' => __('dashboard.hr.pending_breakdown_format', [
                    'leaves' => $pendingLeavesCount,
                    'advances' => $pendingAdvancesCount,
                ]),
            ],
            'projected_payroll' => [
                'label' => __('dashboard.hr.projected_payroll'),
                'value' => round($projectedPayroll, 2),
                'currency' => __('dashboard.currency'),
                'breakdown' => [
                    'base_salaries' => (float) $totalBaseSalaries,
                    'bonuses' => (float) $totalBonuses,
                    'deductions' => (float) $totalDeductions,
                    'advance_installments' => (float) $totalInstallments,
                ],
            ],
        ];
    }

    private function getAttentionSignals(int $perPage): LengthAwarePaginator
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->where(function ($q) {
                $q->where('status', 'Late')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'Absent')
                            ->where('is_exception', false);
                    });
            })
            ->with('user:id,name,email')
            ->latest('date')
            ->paginate($perPage)
            ->through(function ($attendance) {
                $isLate = $attendance->status === 'Late';

                return [
                    'id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'user_name' => $attendance->user->name ?? 'N/A',
                    'date' => $attendance->date,
                    'type' => $isLate ? 'High Attention' : 'Medium Attention',
                    'reason' => $isLate
                        ? __('dashboard.hr.signals.late_reason')
                        : __('dashboard.hr.signals.unexcused_absence_reason'),
                ];
            });
    }

    private function getOperationalReadiness(): array
    {
        $totalEmployees = User::role(['Employee', 'Manager', 'HR'])
            ->where('status', 'active')
            ->count();

        $verifiedAttendanceCount = Attendance::whereDate('date', Carbon::today())
            ->whereNotNull('check_out')
            ->count();

        $attendanceVerification = $totalEmployees > 0
            ? round(($verifiedAttendanceCount / $totalEmployees) * 100)
            : 0;

        $totalLeaves = LeaveRequest::count();
        $approvedLeaves = LeaveRequest::where('status', 'approved')->count();

        $leaveApprovals = $totalLeaves > 0
            ? round(($approvedLeaves / $totalLeaves) * 100)
            : 100;

        $totalEvaluations = Evaluation::count();
        $completedEvaluations = Evaluation::where('status', 'completed')->count();

        $evaluationsCompletion = $totalEvaluations > 0
            ? round(($completedEvaluations / $totalEvaluations) * 100)
            : 0;

        $currentMonthYear = Carbon::now()->format('Y-m');
        $isPayrollFinalized = Payroll::where('month_year', $currentMonthYear)
            ->where('status', 'finalized')
            ->exists();

        $payrollReconciliation = $isPayrollFinalized ? 100 : 0;

        return [
            'attendance_verification' => $attendanceVerification,
            'leave_approvals' => $leaveApprovals,
            'evaluations_completion' => $evaluationsCompletion,
            'payroll_reconciliation' => $payrollReconciliation,
        ];
    }
}
