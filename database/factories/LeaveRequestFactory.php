<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 days', '+30 days')->format('Y-m-d');
        $endDate = date('Y-m-d', strtotime($startDate.' + 2 days'));

        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'status' => LeaveStatus::Pending,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => 3.00,
            'reason' => fake()->sentence(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approvedByManager(?User $manager = null): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::ApprovedByManager,
            'reviewed_by' => $manager?->id ?? User::factory(),
        ]);
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Approved,
            'reviewed_by' => $reviewer?->id ?? User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Schedule conflict'): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Cancelled,
        ]);
    }
}
