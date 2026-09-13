<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can login with valid credentials and receive jwt token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('secret123'),
        'role' => UserRole::Employee,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => [
                'id',
                'name',
                'email',
                'role',
            ],
        ]);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid credentials']);
});

test('authenticated user can fetch their profile via me endpoint', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

test('authenticated user can refresh token', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/refresh');

    $response->assertStatus(200)
        ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out']);
});
