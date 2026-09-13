<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('happy path: submit -> manager approve -> hr approve with database history audit', function () {
    $manager = User::factory()->manager()->create();
    $hr = User::factory()->hr()->create();
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

    // 1. Submit
    $empToken = tokenFor($employee);
    $submitRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(6)->format('Y-m-d'), // 2 days
            'reason' => 'Annual vacation',
        ]);
    $submitRes->assertStatus(201)
        ->assertJsonPath('data.status', LeaveStatus::Pending->value);
    $requestId = $submitRes->json('data.id');

    // 2. Manager Approve
    $mgrToken = tokenFor($manager);
    $mgrRes = $this->withHeader('Authorization', "Bearer {$mgrToken}")
        ->postJson("/api/v1/leave-requests/{$requestId}/approve-manager", [
            'note' => 'Approved by direct manager',
        ]);
    $mgrRes->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::ApprovedByManager->value)
        ->assertJsonPath('data.manager.id', $manager->id);

    // 3. HR Final Approve
    $hrToken = tokenFor($hr);
    $hrRes = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->postJson("/api/v1/leave-requests/{$requestId}/approve-hr", [
            'note' => 'HR finalized and confirmed balance',
        ]);
    $hrRes->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::Approved->value)
        ->assertJsonPath('data.hr.id', $hr->id);

    // 4. Verify decision history has 2 transitions recorded
    $historyRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->getJson("/api/v1/leave-requests/{$requestId}/history");

    $historyRes->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.previous_status', LeaveStatus::Pending->value)
        ->assertJsonPath('data.0.new_status', LeaveStatus::ApprovedByManager->value)
        ->assertJsonPath('data.0.reviewer.id', $manager->id)
        ->assertJsonPath('data.1.previous_status', LeaveStatus::ApprovedByManager->value)
        ->assertJsonPath('data.1.new_status', LeaveStatus::Approved->value)
        ->assertJsonPath('data.1.reviewer.id', $hr->id);
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
        ->postJson("/api/v1/leave-requests/{$request->id}/reject", [
            'rejection_reason' => 'Too many people on leave that week',
            'note' => 'Team coverage required',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::Rejected->value)
        ->assertJsonPath('data.rejection_reason', 'Too many people on leave that week');
});

test('employee can cancel own pending request', function () {
    $employee = User::factory()->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $empToken = tokenFor($employee);
    $response = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->postJson("/api/v1/leave-requests/{$request->id}/cancel");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', LeaveStatus::Cancelled->value);
});

test('employee cannot cancel an already approved request', function () {
    $employee = User::factory()->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
    ]);

    $empToken = tokenFor($employee);
    $response = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->postJson("/api/v1/leave-requests/{$request->id}/cancel");

    $response->assertStatus(403);
});

test('hr cannot approve pending request before manager approval', function () {
    $hr = User::factory()->hr()->create();
    $employee = User::factory()->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $hrToken = tokenFor($hr);
    $response = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->postJson("/api/v1/leave-requests/{$request->id}/approve-hr");

    // Policy blocks approveByHR on pending request (must be approved_by_manager)
    $response->assertStatus(403);
});
