<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manager sees only their team requests in manager queue', function () {
    $managerA = User::factory()->manager()->create();
    $managerB = User::factory()->manager()->create();

    $teamMemberA = User::factory()->employee($managerA)->create();
    $teamMemberB = User::factory()->employee($managerB)->create();

    $type = LeaveType::factory()->noBalanceRequired()->create();

    // Request from Manager A's team
    LeaveRequest::factory()->create([
        'user_id' => $teamMemberA->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    // Request from Manager B's team
    LeaveRequest::factory()->create([
        'user_id' => $teamMemberB->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $mgrTokenA = tokenFor($managerA);

    $response = $this->withHeader('Authorization', "Bearer {$mgrTokenA}")
        ->getJson('/api/v1/manager/leave-requests');
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.id', $teamMemberA->id);
});

test('manager cannot approve request of employee outside their team', function () {
    $managerA = User::factory()->manager()->create();
    $managerB = User::factory()->manager()->create();

    $otherEmployee = User::factory()->employee($managerB)->create();
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $otherEmployee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
    ]);

    $mgrTokenA = tokenFor($managerA);

    $response = $this->withHeader('Authorization', "Bearer {$mgrTokenA}")
        ->postJson("/api/v1/leave-requests/{$request->id}/approve-manager");

    $response->assertStatus(403);
});

test('hr sees all requests across all employees with filters', function () {
    $hr = User::factory()->hr()->create();
    $emp1 = User::factory()->create();
    $emp2 = User::factory()->create();

    $typeA = LeaveType::factory()->noBalanceRequired()->create(['name' => 'Type A']);
    $typeB = LeaveType::factory()->noBalanceRequired()->create(['name' => 'Type B']);

    LeaveRequest::factory()->create([
        'user_id' => $emp1->id,
        'leave_type_id' => $typeA->id,
        'status' => LeaveStatus::Pending,
    ]);

    LeaveRequest::factory()->create([
        'user_id' => $emp2->id,
        'leave_type_id' => $typeB->id,
        'status' => LeaveStatus::Approved,
    ]);

    $hrToken = tokenFor($hr);

    // 1. HR sees both without filter
    $allRes = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->getJson('/api/v1/hr/leave-requests');
    $allRes->assertStatus(200)->assertJsonCount(2, 'data');

    // 2. Filter by status=pending
    $pendingRes = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->getJson('/api/v1/hr/leave-requests?status=pending');
    $pendingRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.id', $emp1->id);

    // 3. Filter by user_id
    $userRes = $this->withHeader('Authorization', "Bearer {$hrToken}")
        ->getJson("/api/v1/hr/leave-requests?user_id={$emp2->id}");
    $userRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.leave_type.id', $typeB->id);
});

test('regular employee cannot access manager or hr queue', function () {
    $employee = User::factory()->create();
    $empToken = tokenFor($employee);

    // Manager queue forbidden
    $mgrRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->getJson('/api/v1/manager/leave-requests');
    $mgrRes->assertStatus(403);

    // HR queue forbidden
    $hrRes = $this->withHeader('Authorization', "Bearer {$empToken}")
        ->getJson('/api/v1/hr/leave-requests');
    $hrRes->assertStatus(403);
});
