<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
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
            $user->update([
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ]);
            if (empty($user->employee_id)) {
                $updateData['employee_id'] = $this->generateOwnerCode();
            }

            $user->update($updateData);
        }

        $token = auth('api')->login($user);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) auth('api')->factory()->getTTL() / 60 .' hours',
            'user' => $user,
        ];
    }
    protected function generateOwnerCode(): string
    {
        $lastOwner = User::where('role', 'Owner')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
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
