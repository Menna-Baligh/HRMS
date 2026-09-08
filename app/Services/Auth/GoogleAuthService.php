<?php
namespace App\Services\Auth;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

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

        if (!$user) {
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'role'              => 'Owner',
                'provider'          => 'google',
                'provider_id'       => $googleUser->getId(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'provider'    => 'google',
                'provider_id' => $googleUser->getId(),
            ]);
        }

        $token = auth('api')->login($user);

        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => (int) auth('api')->factory()->getTTL() / 60 . ' hours',
            'user'         => $user,
        ];
    }
}
