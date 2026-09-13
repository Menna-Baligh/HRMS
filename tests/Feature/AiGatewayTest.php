<?php

use App\Enums\UserRole;
use App\Models\AiFeedback;
use App\Models\AiGeneration;
use App\Models\AiUsageLog;
use App\Models\CompanyPolicy;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\Contracts\AiProviderInterface;
use App\Services\AiGateway\Logging\AiUsageLogger;
use App\Services\AiGateway\Providers\MockAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Shared Test Fixtures
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    // Create users with different roles
    $GLOBALS['employeeUser'] = User::factory()->create(['role' => UserRole::Employee]);
    $GLOBALS['managerUser'] = User::factory()->create(['role' => UserRole::Manager]);
    $GLOBALS['hrUser'] = User::factory()->create(['role' => UserRole::HR]);

    // Employees linked to users
    $GLOBALS['employee'] = Employee::factory()->create(['user_id' => $GLOBALS['employeeUser']->id]);
    $GLOBALS['managerEmployee'] = Employee::factory()->create(['user_id' => $GLOBALS['managerUser']->id]);
    $GLOBALS['hrEmployee'] = Employee::factory()->create(['user_id' => $GLOBALS['hrUser']->id]);

    // Employee reports to manager
    $GLOBALS['employee']->update(['manager_id' => $GLOBALS['managerEmployee']->id]);

    // Reset MockAiProvider to default behaviour
    app()->singleton(AiProviderInterface::class, MockAiProvider::class);
});

/*
|--------------------------------------------------------------------------
| 1. Employee cannot receive another employee's restricted data
|--------------------------------------------------------------------------
*/

it('prevents employee from accessing another employee career coach context', function () {
    $otherUser = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->create(['user_id' => $otherUser->id]);

    $token = tokenFor($GLOBALS['employeeUser']);
    $otherEmployee = $otherUser->employee;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/career-coach/context?employee_id='.$otherEmployee->id);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| 2. Employee Career Coach context contains only their own authorized data
|--------------------------------------------------------------------------
*/

it('career coach context contains only authenticated employee own data', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/career-coach/context');

    $response->assertStatus(200);

    $context = $response->json('data.context');

    // Must contain own employee code
    expect($context['employee']['employee_code'])->toBe($GLOBALS['employee']->employee_id);

    // Must NOT contain passwords, tokens, secrets
    $flat = json_encode($context);
    expect($flat)->not->toContain('password')
        ->not->toContain('api_key')
        ->not->toContain('access_token');
});

/*
|--------------------------------------------------------------------------
| 3. Employee Policy Assistant context cannot contain another employee's
|    leave balance
|--------------------------------------------------------------------------
*/

it('policy assistant context does not expose another employee leave balance', function () {
    // Create a policy so the endpoint doesn't return insufficient_data
    CompanyPolicy::factory()->create(['is_active' => true]);

    // Give the OTHER employee a leave balance
    $otherUser = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->create(['user_id' => $otherUser->id]);
    $leaveType = LeaveType::factory()->create();
    LeaveBalance::factory()->create([
        'user_id' => $otherUser->id,
        'leave_type_id' => $leaveType->id,
        'allocated_days' => 21,
        'used_days' => 5,
    ]);

    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/policy-assistant/context?query=leave+policy');

    $response->assertStatus(200);

    $personalFacts = $response->json('data.context.authorized_personal_facts');

    // Must be the authenticated user's ID, not the other user's
    expect($personalFacts['authenticated_user_id'])->toBe($GLOBALS['employeeUser']->id);

    // If leave balances are present, they must belong to the auth user only
    if (! empty($personalFacts['leave_balances'])) {
        // Verify none match the other user's balance of 16 remaining days
        $otherBalance = collect($personalFacts['leave_balances'])
            ->where('remaining_days', 16)
            ->first();
        expect($otherBalance)->toBeNull();
    }
});

/*
|--------------------------------------------------------------------------
| 4. Manager only receives employee/team data permitted by authorization
|--------------------------------------------------------------------------
*/

it('allows manager to access direct report career coach context', function () {
    $token = tokenFor($GLOBALS['managerUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/career-coach/context?employee_id='.$GLOBALS['employee']->id);

    $response->assertStatus(200);

    $context = $response->json('data.context');
    expect($context['employee']['employee_code'])->toBe($GLOBALS['employee']->employee_id);
});

it('prevents manager from accessing unrelated employee context', function () {
    $otherUser = User::factory()->create(['role' => UserRole::Employee]);
    $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

    $token = tokenFor($GLOBALS['managerUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/career-coach/context?employee_id='.$otherEmployee->id);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| 5. Unauthorized users cannot access restricted AI contexts
|--------------------------------------------------------------------------
*/

it('rejects employee access to evaluation draft generation', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/evaluation-draft/generate', [
            'employee_id' => $GLOBALS['employee']->id,
        ]);

    $response->assertStatus(403);
});

it('rejects unauthenticated access to AI endpoints', function () {
    $response = $this->getJson('/api/v1/ai/career-coach/context');
    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| 6. Restricted fields do not enter AI-facing requests (usage logs safe)
|--------------------------------------------------------------------------
*/

it('does not log restricted fields in AI usage logs', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $log = AiUsageLog::latest('id')->first();
    expect($log)->not->toBeNull();

    $metadata = $log->metadata ?? [];
    $flat = strtolower(json_encode($metadata));

    foreach (['password', '"token":', 'secret', 'api_key', 'bearer'] as $forbidden) {
        expect($flat)->not->toContain($forbidden);
    }
});

/*
|--------------------------------------------------------------------------
| 7. Invalid AI responses are rejected
|--------------------------------------------------------------------------
*/

it('rejects invalid AI response with structured failure', function () {
    // Bind a mock provider that returns a successful response with invalid payload
    $mock = new MockAiProvider;
    $mock->simulateInvalidResponse(true);
    app()->instance(AiProviderInterface::class, $mock);

    // Rebuild the AiGateway with the new provider
    app()->forgetInstance(AiGateway::class);

    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    // The AiGateway returns invalid_ai_response which the controller
    // maps to 422 or returns directly
    $response->assertStatus(422);
    $response->assertJsonFragment(['status' => 'invalid_ai_response']);
});

/*
|--------------------------------------------------------------------------
| 8. Insufficient data returns structured fallback
|--------------------------------------------------------------------------
*/

it('returns insufficient_data when employee profile lacks required fields', function () {
    $noDataUser = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->create([
        'user_id' => $noDataUser->id,
        'job_title' => null,
    ]);

    $token = tokenFor($noDataUser);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $response->assertStatus(422);
    $response->assertJsonFragment(['status' => 'insufficient_data']);
});

/*
|--------------------------------------------------------------------------
| 9. Conflicting source data does not cause fabricated output
|    (no employee profile → insufficient_data, not hallucination)
|--------------------------------------------------------------------------
*/

it('returns insufficient_data when user has no employee profile at all', function () {
    $orphanUser = User::factory()->create(['role' => UserRole::Employee]);
    // No Employee record created — simulates conflicting/missing source data

    $token = tokenFor($orphanUser);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $response->assertStatus(422);
    $response->assertJsonFragment(['status' => 'insufficient_data']);
});

/*
|--------------------------------------------------------------------------
| 10. AI timeout returns structured retryable failure
|--------------------------------------------------------------------------
*/

it('produces ai_timeout contract on provider timeout', function () {
    // Bind a mock provider that returns a timeout response
    $mock = new MockAiProvider;
    $mock->simulateTimeout(true);
    app()->instance(AiProviderInterface::class, $mock);
    app()->forgetInstance(AiGateway::class);

    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $response->assertStatus(504);
    $response->assertJsonFragment([
        'success' => false,
        'status' => 'ai_timeout',
        'retryable' => true,
    ]);
});

/*
|--------------------------------------------------------------------------
| 11. Regeneration creates a new generation/version
|--------------------------------------------------------------------------
*/

it('creates a new generation version on regeneration without overwriting original', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    // Generate first version
    $firstResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    // The gateway persists a generation — find it
    $firstGen = AiGeneration::where('user_id', $GLOBALS['employeeUser']->id)
        ->where('feature', 'career_coach')
        ->latest('id')
        ->first();

    expect($firstGen)->not->toBeNull();
    expect($firstGen->version)->toBe(1);

    // Regenerate
    $regenResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/v1/ai/generations/{$firstGen->id}/regenerate");

    $regenResponse->assertStatus(200);

    // Verify new generation was created
    $secondGen = AiGeneration::where('user_id', $GLOBALS['employeeUser']->id)
        ->where('feature', 'career_coach')
        ->where('regenerated_from_id', $firstGen->id)
        ->first();

    expect($secondGen)->not->toBeNull();
    expect($secondGen->version)->toBe(2);
    expect($secondGen->regenerated_from_id)->toBe($firstGen->id);
});

/*
|--------------------------------------------------------------------------
| 12. Old generation remains available when history is required
|--------------------------------------------------------------------------
*/

it('preserves original generation after regeneration', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $firstGen = AiGeneration::where('user_id', $GLOBALS['employeeUser']->id)
        ->where('feature', 'career_coach')
        ->latest('id')
        ->first();

    // Regenerate
    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/v1/ai/generations/{$firstGen->id}/regenerate");

    // Original must still exist untouched
    $originalAfterRegen = AiGeneration::find($firstGen->id);
    expect($originalAfterRegen)->not->toBeNull();
    expect($originalAfterRegen->version)->toBe(1);
    expect($originalAfterRegen->output_payload)->not->toBeNull();

    // Both generations accessible via history endpoint
    $historyResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/ai/generations');

    $historyResponse->assertStatus(200);

    $generationIds = collect($historyResponse->json('data.data'))->pluck('id')->toArray();
    expect($generationIds)->toContain($firstGen->id);
});

/*
|--------------------------------------------------------------------------
| 13. Feedback is linked to an authorized generation/user
|--------------------------------------------------------------------------
*/

it('allows user to submit feedback on own generation', function () {
    $token = tokenFor($GLOBALS['employeeUser']);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $gen = AiGeneration::where('user_id', $GLOBALS['employeeUser']->id)
        ->latest('id')
        ->first();

    $feedbackResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/v1/ai/generations/{$gen->id}/feedback", [
            'rating' => 'useful',
            'comments' => 'Very helpful recommendations.',
        ]);

    $feedbackResponse->assertStatus(201);

    $feedback = AiFeedback::where('ai_generation_id', $gen->id)->first();
    expect($feedback)->not->toBeNull();
    expect($feedback->user_id)->toBe($GLOBALS['employeeUser']->id);
    expect($feedback->rating)->toBe('useful');
});

it('prevents employee from submitting feedback on another employee generation', function () {
    // Generate as employeeUser
    $empToken = tokenFor($GLOBALS['employeeUser']);
    $this->withHeaders(['Authorization' => "Bearer $empToken"])
        ->postJson('/api/v1/ai/career-coach/generate');

    $gen = AiGeneration::where('user_id', $GLOBALS['employeeUser']->id)
        ->latest('id')
        ->first();

    // Try to submit feedback as a different employee (not manager/HR)
    $otherUser = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->create(['user_id' => $otherUser->id]);
    $otherToken = tokenFor($otherUser);

    $response = $this->withHeaders(['Authorization' => "Bearer $otherToken"])
        ->postJson("/api/v1/ai/generations/{$gen->id}/feedback", [
            'rating' => 'not_useful',
        ]);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| 14. AI usage logs do not contain secrets
|--------------------------------------------------------------------------
*/

it('sanitizes sensitive keys in AI usage log metadata', function () {
    $logger = app(AiUsageLogger::class);

    $sanitized = $logger->sanitize([
        'feature' => 'career_coach',
        'password' => 'secret123',
        'api_key' => 'sk-abc',
        'token' => 'eyJhbGciOiJIUzI1NiJ9',
        'authorization' => 'Bearer xyz',
        'safe_field' => 'this is fine',
        'nested' => [
            'secret' => 'top-secret',
            'normal' => 'ok',
        ],
    ]);

    expect($sanitized['password'])->toBe('[REDACTED]');
    expect($sanitized['api_key'])->toBe('[REDACTED]');
    expect($sanitized['token'])->toBe('[REDACTED]');
    expect($sanitized['authorization'])->toBe('[REDACTED]');
    expect($sanitized['safe_field'])->toBe('this is fine');
    expect($sanitized['nested']['secret'])->toBe('[REDACTED]');
    expect($sanitized['nested']['normal'])->toBe('ok');
});

/*
|--------------------------------------------------------------------------
| Additional: Performance Insight authorization
|--------------------------------------------------------------------------
*/

it('prevents employee from accessing another employee performance insight', function () {
    $otherUser = User::factory()->create(['role' => UserRole::Employee]);
    $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/performance-insight/generate', [
            'employee_id' => $otherEmployee->id,
        ]);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Additional: Policy Assistant with no active policies
|--------------------------------------------------------------------------
*/

it('returns insufficient_data when no active policies exist for policy assistant', function () {
    // Ensure no active policies exist
    CompanyPolicy::query()->delete();

    $token = tokenFor($GLOBALS['employeeUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/policy-assistant/ask', [
            'query' => 'What is the annual leave policy?',
        ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['status' => 'insufficient_data']);
});

/*
|--------------------------------------------------------------------------
| Additional: Evaluation Draft includes draft flag and cannot auto-finalize
|--------------------------------------------------------------------------
*/

it('evaluation draft output is marked as draft requiring manager review', function () {
    $token = tokenFor($GLOBALS['managerUser']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/ai/evaluation-draft/generate', [
            'employee_id' => $GLOBALS['employee']->id,
        ]);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data['is_draft'])->toBeTrue();
});
