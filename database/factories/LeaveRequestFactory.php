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
            'requested_days' => 3.00,
            'reason' => fake()->sentence(),
            'attachment_path' => null,
            'manager_id' => null,
            'hr_id' => null,
            'rejection_reason' => null,
        ];
    }

    public function approvedByManager(?User $manager = null): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::ApprovedByManager,
            'manager_id' => $manager?->id ?? User::factory()->manager(),
        ]);
    }

    public function approved(?User $manager = null, ?User $hr = null): static
    {
        return $this->state(fn () => [
            'status' => LeaveStatus::Approved,
            'manager_id' => $manager?->id ?? User::factory()->manager(),
            'hr_id' => $hr?->id ?? User::factory()->hr(),
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
