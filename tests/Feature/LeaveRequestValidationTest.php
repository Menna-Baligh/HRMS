<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('end date before start date is rejected', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->create(['requires_balance' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'reason' => 'Backwards dates',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['end_date']);
});

test('attachment is required if leave type has requires_attachment = true', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->requiresAttachment()->noBalanceRequired()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Sick leave without attachment',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', "An attachment is required for leave type [{$type->name}].");
});

test('request succeeds when valid attachment is uploaded for requiring leave type', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->requiresAttachment()->noBalanceRequired()->create();

    $file = UploadedFile::fake()->create('doctor_note.pdf', 500, 'application/pdf');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Sick leave with doctor note',
            'attachment' => $file,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.has_attachment', true);

    $leaveRequest = LeaveRequest::first();
    expect($leaveRequest->attachment_path)->not->toBeNull();
    Storage::disk('local')->assertExists($leaveRequest->attachment_path);
});

test('overlapping dates with existing pending or approved leave are rejected', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $startDate = now()->addDays(10)->format('Y-m-d');
    $endDate = now()->addDays(15)->format('Y-m-d');

    // Existing active request
    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'status' => LeaveStatus::Pending,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'requested_days' => 6,
    ]);

    // Try to book overlapping period (e.g. days 12 to 18)
    $overlapStart = now()->addDays(12)->format('Y-m-d');
    $overlapEnd = now()->addDays(18)->format('Y-m-d');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => $overlapStart,
            'end_date' => $overlapEnd,
            'reason' => 'Overlapping request',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', "Date overlap: you already have an active leave request during [{$overlapStart}] – [{$overlapEnd}].");
});

test('rejected or cancelled leaves do not block new requests on the same dates', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);
    $type = LeaveType::factory()->noBalanceRequired()->create();

    $startDate = now()->addDays(10)->format('Y-m-d');
    $endDate = now()->addDays(12)->format('Y-m-d');

    // Existing rejected request
    LeaveRequest::factory()->rejected()->create([
        'user_id' => $user->id,
        'leave_type_id' => $type->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    // Should succeed because previous one was rejected
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Re-applying for the same dates',
        ]);

    $response->assertStatus(201);
});
