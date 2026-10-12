<?php

namespace App\Services\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterService
{
    public function __construct(protected OtpService $otpService) {}

    public function register(array $data): array
    {
        $ownerCode = $this->generateOwnerCode();
        $user = User::create([
            'employee_id' => $ownerCode,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'provider' => $data['provider'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'Owner',
        ]);

        $user->assignRole('Owner');

        // Generate OTP and send it to the registered email
        $this->otpService->generate($user->email);

        // Generate JWT access token
        $token = JWTAuth::fromUser($user);
        $user = $user->fresh();

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (auth('api')->factory()->getTTL() / 60).' hours',
            'user' => new UserResource($user),
        ];

    }
    protected function generateOwnerCode(): string
    {
        $lastOwner = User::where('role', 'Owner')
            ->whereNotNull('employee_id')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastOwner) {
            return 'OWN-0001';
        }

        $lastNumber = (int) filter_var($lastOwner->employee_id, FILTER_SANITIZE_NUMBER_INT);
        $nextNumber = $lastNumber + 1;

        return 'OWN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
