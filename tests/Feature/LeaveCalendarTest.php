<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calendar endpoint returns only approved leaves within specified date range', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $startDate = '2026-10-10';
    $endDate = '2026-10-15';

    // 1. Approved leave inside the window
    $approvedInside = LeaveRequest::factory()->approved()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'start_date' => '2026-10-11',
        'end_date' => '2026-10-13',
    ]);

    // 2. Pending leave inside the window (should NOT show)
    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
        'start_date' => '2026-10-12',
        'end_date' => '2026-10-14',
    ]);

    // 3. Approved leave outside window (should NOT show)
    LeaveRequest::factory()->approved()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/calendar/leaves?start_date={$startDate}&end_date={$endDate}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $approvedInside->id);
});
