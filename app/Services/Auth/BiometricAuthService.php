<?php

namespace App\Services\Auth;

use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BiometricAuthService
{
    public function toggleBiometrics(User $user): array
    {
        if ($user->biometric_token) {
            $user->forceFill([
                'biometric_token' => null,
            ])->save();

            return [
                'is_enabled' => false,
                'biometric_token' => null,
                'message' => __('auth.biometric_disabled_success'),
            ];
        }

        $biometricToken = (string) Str::uuid();

        $user->forceFill([
            'biometric_token' => $biometricToken,
        ])->save();

        return [
            'is_enabled' => true,
            'biometric_token' => $biometricToken,
            'message' => __('auth.biometric_enabled_success'),
        ];
    }

    public function loginWithBiometrics(string $biometricToken): array
    {
        $user = User::where('biometric_token', $biometricToken)->first();

        if (! $user) {
            throw new Exception(__('auth.biometric_invalid_token'), Response::HTTP_UNAUTHORIZED);
        }

        if ($user->status !== 'active') {
            throw new Exception(__('auth.account_inactive'), Response::HTTP_FORBIDDEN);
        }

        $token = auth('api')->login($user);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() / 60 .' hours',
            'user' => auth('api')->user(),
        ];
    }
}
