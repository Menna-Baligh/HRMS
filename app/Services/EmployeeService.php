<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
            ]);

            $user->assignRole($data['role']);

            $user->employee()->create([
                'employee_id' => $this->generateUniqueEmployeeId(),
                'job_title' => $data['job_title'],
                'employment_type' => $data['employment_type'],
                'start_date' => $data['start_date'],
                'department_id' => $data['department_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => 'active',
            ]);

            return $user->load('employee.department', 'employee.manager');
        });
    }

    private function generateUniqueEmployeeId(): string
    {
        $nextId = (Employee::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.date('Y').'-'.str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }
    public function getEmployeeById(int $id): Employee
    {
        return Employee::with(['user', 'department', 'manager.user'])->findOrFail($id);
    }
    public function updateHrFields(Employee $employee, array $data): Employee
    {
        $employee->update(array_filter($data, fn ($value) => $value !== null));
        return $employee->load(['user', 'department', 'manager.user']);
    }
    public function updateProfile(User $user, array $data): Employee
    {
        return DB::transaction(function () use ($user, $data) {
            $userData = array_intersect_key($data, array_flip(['name', 'avatar']));
            if (!empty($userData)) {
                $user->update($userData);
                $user->refresh();
            }
            $employeeData = array_intersect_key($data, array_flip(['phone', 'address']));
            $employee = $user->employee;
            if (!$employee) {
                throw new ModelNotFoundException('Employee profile not found for this user.');
            }
            if (!empty($employeeData)) {
                $employee->update($employeeData);
            }
            return $employee->load(['user', 'department', 'manager.user']);
        });
    }
}
