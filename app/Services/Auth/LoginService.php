<?php

namespace App\Services\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public function __construct(protected RefreshTokenService $refreshTokenService) {}

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

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        return [
            'access_token'       => $token,
            'refresh_token'      => $refreshToken,
            'token_type'         => 'bearer',
            'expires_in'         => '15 minutes',
            'refresh_expires_in' => '30 days',
            'user'               => $user,
        ];
    }
}
