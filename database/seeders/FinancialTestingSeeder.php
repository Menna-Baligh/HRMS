<?php

namespace Database\Seeders;

use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancialTestingSeeder extends Seeder
{
    public function run(): void
    {
        $employeeId = 6; // Noran Baligh
        $hrId = 7;       // Asem (HR)

        User::where('id', $employeeId)->update(['salary' => 8000.00]);

        Bonus::create([
            'user_id' => $employeeId,
            'approved_by_user_id' => $hrId,
            'incentive_type' => 'Performance Bonus',
            'amount' => 2000.00,
            'target_month' => '2026-10',
            'status' => 'approved',
        ]);

        Bonus::create([
            'user_id' => $employeeId,
            'approved_by_user_id' => $hrId,
            'incentive_type' => 'Project Completion',
            'amount' => 500.00,
            'target_month' => '2026-10',
            'status' => 'approved',
        ]);

        Deduction::create([
            'user_id' => $employeeId,
            'reason' => 'Late Attendance',
            'amount' => 300.00,
            'date' => '2026-10-05',
            'status' => 'queued',
        ]);

        Deduction::create([
            'user_id' => $employeeId,
            'reason' => 'Unexcused Absence',
            'amount' => 200.00,
            'date' => '2026-10-12',
            'status' => 'queued',
        ]);

        SalaryAdvance::create([
            'user_id' => $employeeId,
            'requested_amount' => 6000.00,
            'repayment_months' => 6,
            'monthly_deduction' => 1000.00,
            'reason' => 'Emergency personal expense',
            'status' => 'approved',
        ]);
    }
}
