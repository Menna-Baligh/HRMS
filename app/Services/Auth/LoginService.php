<?php

namespace App\Services\Auth;

use Illuminate\Auth\Access\AuthorizationException;
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

        if ($user->status === 'inactive') {
            auth('api')->logout();
            throw new AuthorizationException(__('auth.account_inactive'));
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() / 60 .' hours',
            'user' => auth('api')->user(),
        ];
    }
}
