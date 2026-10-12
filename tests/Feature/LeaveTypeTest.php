<?php

use App\Enums\UserRole;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list active leave types', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    LeaveType::factory()->create(['name' => 'Active Type', 'is_active' => true]);
    LeaveType::factory()->create(['name' => 'Inactive Type', 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/leaves/leave-types');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('hr user can see all leave types including inactive', function () {
    $hr = User::factory()->hr()->create();
    $token = tokenFor($hr);

    LeaveType::factory()->create(['name' => 'Active Type', 'is_active' => true]);
    LeaveType::factory()->create(['name' => 'Inactive Type', 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/leaves/leave-types');

    $response->assertStatus(200);
});

test('hr or owner can create a leave type', function () {
    $hr = User::factory()->hr()->create();
    $token = tokenFor($hr);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/leaves/leave-types', [
            'name' => 'Maternity Leave',
            'description' => 'Maternity leave description',
            'default_days' => 90,
            'is_active' => true,
            'requires_balance' => true,
            'requires_attachment' => true,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Maternity Leave')
        ->assertJsonPath('data.requires_attachment', true);

    $this->assertDatabaseHas('leave_types', [
        'name' => 'Maternity Leave',
        'requires_attachment' => true,
    ]);
});

test('regular employee cannot create a leave type', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $token = tokenFor($employee);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/leaves/leave-types', [
            'name' => 'Hacking Leave',
            'default_days' => 10,
        ]);

    $response->assertStatus(403);
});

test('hr can update a leave type', function () {
    $hr = User::factory()->hr()->create();
    $token = tokenFor($hr);
    $type = LeaveType::factory()->create(['name' => 'Old Name']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/leaves/leave-types/{$type->id}", [
            'name' => 'New Name',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'name' => 'New Name']);
});

test('hr can deactivate a leave type', function () {
    $hr = User::factory()->hr()->create();
    $token = tokenFor($hr);
    $type = LeaveType::factory()->create(['is_active' => true]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/leaves/leave-types/{$type->id}/deactivate");

    $response->assertStatus(200);
    $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'is_active' => false]);
});
