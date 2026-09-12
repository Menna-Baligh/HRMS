<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Leave',
            'description' => fake()->sentence(),
            'is_active' => true,
            'requires_balance' => true,
            'requires_attachment' => false,
        ];
    }

    public function requiresAttachment(): static
    {
        return $this->state(fn () => [
            'requires_attachment' => true,
        ]);
    }

    public function noBalanceRequired(): static
    {
        return $this->state(fn () => [
            'requires_balance' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
