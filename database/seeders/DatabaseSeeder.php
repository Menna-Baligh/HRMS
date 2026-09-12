<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LeaveTypeSeeder::class);

        // Standard test accounts
        $owner = User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'System Owner',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner,
                'email_verified_at' => now(),
            ]
        );

        $hr = User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Specialist',
                'password' => Hash::make('password'),
                'role' => UserRole::HR,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Engineering Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'email_verified_at' => now(),
            ]
        );

        $employee = User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Software Engineer',
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
                'manager_id' => $manager->id,
                'email_verified_at' => now(),
            ]
        );

        // Seed balances for the test employee
        $annualLeave = LeaveType::where('name', 'Annual Leave')->first();
        $sickLeave = LeaveType::where('name', 'Sick Leave')->first();
        $currentYear = (int) date('Y');

        if ($annualLeave) {
            LeaveBalance::updateOrCreate(
                ['user_id' => $employee->id, 'leave_type_id' => $annualLeave->id, 'year' => $currentYear],
                ['allocated_days' => 21.00, 'used_days' => 0.00]
            );
        }

        if ($sickLeave) {
            LeaveBalance::updateOrCreate(
                ['user_id' => $employee->id, 'leave_type_id' => $sickLeave->id, 'year' => $currentYear],
                ['allocated_days' => 14.00, 'used_days' => 0.00]
            );
        }
    }
}
