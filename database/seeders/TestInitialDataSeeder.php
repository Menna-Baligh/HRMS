<?php

namespace Database\Seeders;

use App\Models\CompanyLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestInitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('12345678');

        $owner = User::updateOrCreate(
            ['id' => 2],
            [
                'employee_id' => null,
                'name' => 'Menna Baligh Hamdy',
                'email' => 'mennabaligh317@gmail.com',
                'locale' => 'ar',
                'role' => 'Owner',
                'job_title' => null,
                'employment_type' => null,
                'start_date' => null,
                'status' => 'active',
                'manager_id' => null,
                'password' => $password,
            ]
        );

        $location = CompanyLocation::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'WorkWise HQ',
                'latitude' => 30.0444196,
                'longitude' => 31.2357116,
                'radius' => 100,
                'is_active' => true,
                'created_by' => $owner->id,
            ]
        );

        $manager = User::updateOrCreate(
            ['id' => 3],
            [
                'employee_id' => 'EMP-2026-00001',
                'name' => 'Manona',
                'email' => 'mennabaligh06@gmail.com',
                'locale' => 'ar',
                'role' => 'Manager',
                'job_title' => 'Software Tester',
                'employment_type' => 'Full-time',
                'start_date' => '2026-09-01',
                'status' => 'active',
                'manager_id' => null,
                'password' => $password,
                'company_location_id' => $location->id,
            ]
        );

        $employee = User::updateOrCreate(
            ['id' => 6],
            [
                'employee_id' => 'EMP-2026-24271',
                'name' => 'Noran Baligh',
                'email' => 'noran@gmail.com',
                'locale' => 'ar',
                'role' => 'Employee',
                'job_title' => 'Frontend Developer',
                'employment_type' => 'Part-time',
                'start_date' => '2026-09-01',
                'status' => 'active',
                'manager_id' => $manager->id,
                'password' => $password,
                'company_location_id' => $location->id,
            ]
        );

        $hr = User::updateOrCreate(
            ['id' => 7],
            [
                'employee_id' => 'EMP-2026-79225',
                'name' => 'Asem',
                'email' => 'hr@test.com',
                'locale' => 'en',
                'role' => 'HR',
                'job_title' => 'Human Resource',
                'employment_type' => 'Full-time',
                'start_date' => '2026-09-01',
                'status' => 'active',
                'manager_id' => null,
                'password' => $password,
                'company_location_id' => $location->id,
            ]
        );

        $owner->update(['company_location_id' => $location->id]);

        if (method_exists($owner, 'syncRoles')) {
            $owner->syncRoles(['Owner']);
            $manager->syncRoles(['Manager']);
            $employee->syncRoles(['Employee']);
            $hr->syncRoles(['HR']);
        }
    }
}
