<?php

namespace Database\Factories;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => (int) date('Y'),
            'allocated_days' => 21.00,
            'used_days' => 0.00,
        ];
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_days' => $attributes['allocated_days'] ?? 21.00,
        ]);
    }

    public function withRemaining(float $days): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $allocated = $attributes['allocated_days'] ?? 21.00;

            return [
                'allocated_days' => $allocated,
                'used_days' => max(0, $allocated - $days),
            ];
        });
    }
}
