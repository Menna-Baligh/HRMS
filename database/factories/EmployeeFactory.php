<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_id' => 'EMP-' . fake()->unique()->numerify('#####'),
            'job_title' => fake()->jobTitle(),
            'employment_type' => 'Full-time',
            'start_date' => now()->subYears(2)->toDateString(),
            'status' => 'active',
            'department_id' => null,
            'manager_id' => null,
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }
}
