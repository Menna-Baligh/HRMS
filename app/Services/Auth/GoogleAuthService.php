<?php

namespace App\Services\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    public function __construct(
        protected RefreshTokenService $refreshTokenService
    ) {}

    public function getGoogleRedirectUrl(): string
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    public function handleGoogleCallback(): array
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user && ! $user->hasRole('Owner')) {
            throw ValidationException::withMessages([
                'email' => 'You are not authorized to log in with Google.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'employee_id' => $this->generateOwnerCode(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'role' => 'Owner',
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('Owner');
        } else {
            $updateData = [
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ];

            if (empty($user->employee_id)) {
                $updateData['employee_id'] = $this->generateOwnerCode();
            }

            $user->update($updateData);
        }

        $token = auth('api')->login($user);

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $user->load(['department', 'companyLocation', 'manager', 'files']);

        return [
            'access_token'       => $token,
            'refresh_token'      => $refreshToken,
            'token_type'         => 'bearer',
            'expires_in'         => '15 minutes',
            'refresh_expires_in' => '30 days',
            'user'               => new UserResource($user),
        ];
    }

    protected function generateOwnerCode(): string
    {
        $lastOwner = User::where('role', 'Owner')
            ->where('employee_id', 'LIKE', 'OWN-%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastOwner) {
            $nextNumber = 1;
        } else {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastOwner->employee_id);
            $nextNumber = $lastNumber + 1;
        }

        do {
            $code = 'OWN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = User::where('employee_id', $code)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $code;
    }
}
