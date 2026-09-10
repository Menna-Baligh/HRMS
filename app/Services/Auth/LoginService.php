<?php

namespace App\Services\Auth;

use Illuminate\Validation\ValidationException;

class LoginService
{
    public function login(array $credentials): array
    {
        if (! $token = auth('api')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }
        $user = auth('api')->user();

        if ($user->employee && $user->employee->status === 'inactive') {
            auth('api')->logout();
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please activate your account first.',
            ]);
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() / 60 .' hours',
            'user' => auth('api')->user(),
        ];
    }
}
