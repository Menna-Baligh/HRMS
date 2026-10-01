<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Payroll;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    
    public function calculateCompanyPayroll(string $monthYear): array
    {
        $existingPayrolls = Payroll::with('user')->where('month_year', $monthYear)->get();

        if ($existingPayrolls->isNotEmpty()) {
            return $existingPayrolls->all();
        }

        $users = User::all();
        $calculatedPayrolls = [];

        foreach ($users as $employee) {
            $calculatedPayrolls[] = $this->calculateEmployeePayrollData($employee, $monthYear);
        }

        return $calculatedPayrolls;
    }

    
    public function finalizeCompanyPayroll(string $monthYear): array
    {
        return DB::transaction(function () use ($monthYear) {
            $users = User::all();
            $finalizedPayrolls = [];

            foreach ($users as $employee) {
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

                $finalizedPayrolls[] = $payroll->load('user');
            }

            return $finalizedPayrolls;
        });
    }

    
    public function getEmployeeSalaryHistory(User $user): array
    {
        $currentMonth = now()->format('Y-m');

        $finalizedPayrolls = Payroll::where('user_id', $user->id)
            ->latest('month_year')
            ->get();

        $history = [];

        $currentMonthFinalized = $finalizedPayrolls->where('month_year', $currentMonth)->first();

        if (! $currentMonthFinalized) {
            $currentEstimated = $this->calculateEmployeePayrollData($user, $currentMonth);
            $history[] = $currentEstimated;
        }

        foreach ($finalizedPayrolls as $payroll) {
            $history[] = $payroll;
        }

        return $history;
    }


    private function calculateEmployeePayrollData(User $employee, string $monthYear): object
    {
        $basicSalary = $employee->salary ?? 0.00;

        $totalBonuses = Bonus::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where('target_month', $monthYear)
            ->sum('amount');

        $totalDeductions = Deduction::where('user_id', $employee->id)
            ->where('status', 'queued')
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$monthYear])
            ->sum('amount');

        $loanInstallment = SalaryAdvance::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->sum('monthly_deduction');

        $netSalary = max(0, ($basicSalary + $totalBonuses) - ($totalDeductions + $loanInstallment));

        $existingPayroll = Payroll::where('user_id', $employee->id)
            ->where('month_year', $monthYear)
            ->first();

        return (object) [
            'id' => $existingPayroll?->id,
            'user_id' => $employee->id,
            'user' => $employee,
            'month_year' => $monthYear,
            'basic_salary' => (float) $basicSalary,
            'total_bonuses' => (float) $totalBonuses,
            'total_deductions' => (float) $totalDeductions,
            'loan_installment' => (float) $loanInstallment,
            'net_salary' => (float) $netSalary,
            'status' => $existingPayroll ? 'finalized' : 'pending_approval',
        ];
    }
}