<?php

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('leave request rejected if user has insufficient balance', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->create(['requires_balance' => true]);

    $year = (int) date('Y');
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'year' => $year,
        'allocated_days' => 5,
        'used_days' => 4, // only 1 day remaining
    ]);

    // Request 3 days
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'), // 3 days
            'reason' => 'Need a vacation',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient leave balance. Requested: 3 day(s), remaining: 1 day(s).');
});

test('leave request succeeds if user has sufficient balance', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->create(['requires_balance' => true]);

    $year = (int) date('Y');
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'year' => $year,
        'allocated_days' => 10,
        'used_days' => 0,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(6)->format('Y-m-d'), // 2 days
            'reason' => '2 day vacation',
        ]);

    $response->assertStatus(201);
});

test('balance is NOT deducted upon creation or manager approval, but IS deducted upon HR approval', function () {
    $manager = User::factory()->manager()->create();
    $hr = User::factory()->hr()->create();
    $employee = User::factory()->employee($manager)->create();

    $type = LeaveType::factory()->create(['requires_balance' => true]);
    $year = (int) date('Y');

    $balance = LeaveBalance::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'year' => $year,
        'allocated_days' => 10,
        'used_days' => 0,
    ]);

    // 1. Employee creates request for 3 days
    $empToken = tokenFor($employee);
    $createResponse = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Annual leave',
        ]);
    $createResponse->assertStatus(201);
    $requestId = $createResponse->json('data.id');

    // Verify balance unchanged
    expect($balance->fresh()->used_days)->toBe('0.00');

    // 2. Manager approves
    $mgrToken = tokenFor($manager);
    $mgrResponse = $this->withHeader('Authorization', "Bearer {$mgrToken}")
        ->postJson("/api/v1/leave-requests/{$requestId}/approve-manager", [
            'note' => 'Looks good to me',
        ]);
    $mgrResponse->assertStatus(200);

    // Verify balance still unchanged
    expect($balance->fresh()->used_days)->toBe('0.00');

    // 3. HR gives final approval
    $hrToken = tokenFor($hr);
    $hrResponse = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->postJson("/api/v1/leave-requests/{$requestId}/approve-hr", [
            'note' => 'HR finalized approval',
        ]);
    $hrResponse->assertStatus(200);

    // Balance MUST now be deducted by 3 days
    $freshBalance = $balance->fresh();
    expect((float) $freshBalance->used_days)->toBe(3.0)
        ->and($freshBalance->remaining_days)->toBe(7.0);
});

test('user can view their leave balances', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->create();

    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'year' => (int) date('Y'),
        'allocated_days' => 15,
        'used_days' => 5,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/leave-balances');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.allocated_days', 15)
        ->assertJsonPath('data.0.used_days', 5)
        ->assertJsonPath('data.0.remaining_days', 10);
});
