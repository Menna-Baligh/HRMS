<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveDecisionHistory;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveDecisionHistory>
 */
class LeaveDecisionHistoryFactory extends Factory
{
    protected $model = LeaveDecisionHistory::class;

    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'reviewer_id' => User::factory()->manager(),
            'previous_status' => LeaveStatus::Pending,
            'new_status' => LeaveStatus::ApprovedByManager,
            'note' => fake()->optional()->sentence(),
            'decided_at' => now(),
        ];
    }
}
