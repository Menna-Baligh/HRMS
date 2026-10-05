<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Payroll;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function calculateCompanyPayroll(string $monthYear, int $perPage = 10): LengthAwarePaginator
    {
        $isFinalized = Payroll::where('month_year', $monthYear)->exists();

        if ($isFinalized) {
            return Payroll::with('user')
                ->where('month_year', $monthYear)
                ->whereDoesntHave('user.roles', function ($q) {
                    $q->whereIn('name', ['Owner', 'HR']);
                })
                ->paginate($perPage);
        }

        $employeesQuery = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['Owner', 'HR']);
        });

        $employees = $employeesQuery->paginate($perPage);

        $employees->getCollection()->transform(function ($employee) use ($monthYear) {
            return $this->calculateEmployeePayrollData($employee, $monthYear);
        });

        return $employees;
    }

    public function finalizeCompanyPayroll(string $monthYear): array
    {
        return DB::transaction(function () use ($monthYear) {
            $alreadyFinalizedUserIds = Payroll::where('month_year', $monthYear)
                ->pluck('user_id')
                ->toArray();

            $pendingEmployees = User::whereNotIn('id', $alreadyFinalizedUserIds)
                ->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['Owner', 'HR']);
                })
                ->get();

            if ($pendingEmployees->isEmpty()) {
                return [];
            }

            $newlyFinalizedPayrolls = [];

            foreach ($pendingEmployees as $employee) {
                $payrollData = $this->calculateEmployeePayrollData($employee, $monthYear);

                $payroll = Payroll::create([
                    'user_id' => $employee->id,
                    'month_year' => $monthYear,
                    'basic_salary' => $payrollData->basic_salary,
                    'total_bonuses' => $payrollData->total_bonuses,
                    'total_deductions' => $payrollData->total_deductions,
                    'loan_installment' => $payrollData->loan_installment,
                    'net_salary' => $payrollData->net_salary,
                    'status' => 'finalized',
                ]);

                Deduction::where('user_id', $employee->id)
                    ->where('status', 'queued')
                    ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$monthYear])
                    ->update(['status' => 'deducted']);

                SendNotificationJob::dispatch(
                    $employee,
                    'payroll_finalized',
                    'financial.notifications.payroll_finalized.title',
                    'financial.notifications.payroll_finalized.body',
                    ['amount' => $payroll->net_salary, 'month' => $monthYear],
                    ['payroll_id' => $payroll->id, 'screen' => 'payslip_details', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                );

                $newlyFinalizedPayrolls[] = $payroll->load('user');
            }

            return $newlyFinalizedPayrolls;
        });
    }

    public function getEmployeeSalaryHistory(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Payroll::where('user_id', $user->id)
            ->latest('month_year')
            ->paginate($perPage);
    }

    private function calculateEmployeePayrollData(User $employee, string $monthYear): object
    {
        $basicSalary = (float) ($employee->salary ?? 0.00);

        $totalBonuses = (float) Bonus::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where('target_month', $monthYear)
            ->sum('amount');

        $totalDeductions = (float) Deduction::where('user_id', $employee->id)
            ->where('status', 'queued')
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$monthYear])
            ->sum('amount');

        $loanInstallment = (float) SalaryAdvance::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->sum('monthly_deduction');

        $netSalary = max(0, ($basicSalary + $totalBonuses) - ($totalDeductions + $loanInstallment));

        $existingPayroll = Payroll::where('user_id', $employee->id)
            ->where('month_year', $monthYear)
            ->first();

        return (object) [
            'id' => $existingPayroll?->id ?? null,
            'user_id' => $employee->id,
            'user' => $employee,
            'month_year' => $monthYear,
            'basic_salary' => $basicSalary,
            'total_bonuses' => $totalBonuses,
            'total_deductions' => $totalDeductions,
            'loan_installment' => $loanInstallment,
            'net_salary' => $netSalary,
            'status' => $existingPayroll ? 'finalized' : 'draft',
            'is_finalized' => (bool) $existingPayroll,
        ];
    }
}
