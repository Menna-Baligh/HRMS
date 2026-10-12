<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calendar endpoint returns only approved leaves within specified date range', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Employee');
    $token = tokenFor($user);
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $startDate = '2026-10-10';
    $endDate = '2026-10-15';

    // 1. Approved leave inside the window (2026-10-11 to 2026-10-13 = 3 days)
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
        ->getJson("/api/calendar?from={$startDate}&to={$endDate}");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.reference', $approvedInside->id)
        ->assertJsonPath('data.0.type', 'leave');
});

