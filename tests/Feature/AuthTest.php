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

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});

test('authenticated user can refresh token', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/refresh');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'expires_in'],
        ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = tokenFor($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout');

    $response->assertStatus(200);
});
