<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('happy path: employee submit -> manager approve with decisions history audit', function () {
    $manager = User::factory()->manager()->create();
    $employee = User::factory()->employee($manager)->create();

    $type = LeaveType::factory()->create(['requires_balance' => true]);
    $year = (int) date('Y');

    LeaveBalance::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'year' => $year,
        'allocated_days' => 15,
        'used_days' => 0,
    ]);

    // 1. Employee Submits Request
    $empToken = tokenFor($employee);
    $submitRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->postJson('/api/leaves/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(6)->format('Y-m-d'), // 2 days
            'reason' => 'Annual vacation',
        ]);
    $submitRes->assertStatus(201)
        ->assertJsonPath('data.status', LeaveStatus::Pending->value);
    $requestId = $submitRes->json('data.id');

    // 2. Manager Approves
    $mgrToken = tokenFor($manager);
    $mgrRes = $this->withHeader('Authorization', "Bearer {$mgrToken}")
        ->patchJson("/api/leaves/leave-requests/{$requestId}/approve");
    $mgrRes->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::Approved->value)
        ->assertJsonPath('data.reviewed_by', $manager->id);

    // 3. Verify decision history has transition recorded
    $historyRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->getJson("/api/leaves/leave-requests/{$requestId}/decisions");

    $historyRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.previous_status', LeaveStatus::Pending->value)
        ->assertJsonPath('data.0.decision', LeaveStatus::Approved->value)
        ->assertJsonPath('data.0.reviewer.id', $manager->id);
});

test('manager can reject pending request with rejection reason', function () {
    $manager = User::factory()->manager()->create();
    $employee = User::factory()->employee($manager)->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $mgrToken = tokenFor($manager);
    $response = $this->withHeader('Authorization', "Bearer {$mgrToken}")
        ->patchJson("/api/leaves/leave-requests/{$request->id}/reject", [
            'rejection_reason' => 'Too many people on leave that week',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::Rejected->value)
        ->assertJsonPath('data.rejection_reason', 'Too many people on leave that week');
});

test('regular employee cannot approve a leave request', function () {
    $employee = User::factory()->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $empToken = tokenFor($employee);
    $response = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->patchJson("/api/leaves/leave-requests/{$request->id}/approve");

    $response->assertStatus(403);
});

test('cannot approve an already approved request', function () {
    $hr = User::factory()->hr()->create();
    $employee = User::factory()->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
    ]);

    $hrToken = tokenFor($hr);
    $response = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->patchJson("/api/leaves/leave-requests/{$request->id}/approve");

    $response->assertStatus(422);
});

