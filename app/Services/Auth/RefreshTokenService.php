<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;

class RefreshTokenService
{
    protected int $ttlDays = 30; 


    public function createRefreshToken(User $user): string
    {
        $plainToken = Str::random(64) . '.' . $user->id;
        $hashedToken = hash('sha256', $plainToken);

        RefreshToken::create([
            'user_id'    => $user->id,
            'token_id'   => $hashedToken,
            'is_revoked' => false,
            'expires_at' => now()->addDays($this->ttlDays),
        ]);

        return $plainToken;
    }


    public function rotateRefreshToken(string $plainToken): array
    {
        $hashedToken = hash('sha256', $plainToken);

        $refreshToken = RefreshToken::where('token_id', $hashedToken)->first();

        if (! $refreshToken || $refreshToken->expires_at->isPast()) {
            throw new Exception(__('auth.invalid_refresh_token'), 401);
        }

        if ($refreshToken->is_revoked) {
            if ($refreshToken->updated_at->gt(now()->subSeconds(10))) {
                $user = $refreshToken->user;
                $newAccessToken = auth('api')->tokenById($user->id);
                $newRefreshToken = $this->createRefreshToken($user);

                return [
                    'access_token'       => $newAccessToken,
                    'refresh_token'      => $newRefreshToken,
                    'token_type'         => 'bearer',
                    'expires_in'         => '15 minutes',
                    'refresh_expires_in' => '30 days',
                    'user'               => $user,
                ];
            }

            throw new Exception(__('auth.invalid_refresh_token'), 401);
        }

        $refreshToken->update(['is_revoked' => true]);

        $user = $refreshToken->user;

        if ($user->status === 'inactive') {
            throw new Exception(__('auth.account_inactive'), 403);
        }

        $newAccessToken = auth('api')->tokenById($user->id);
        $newRefreshToken = $this->createRefreshToken($user);

        return [
            'access_token'       => $newAccessToken,
            'refresh_token'      => $newRefreshToken,
            'token_type'         => 'bearer',
            'expires_in'         => '15 minutes',
            'refresh_expires_in' => '30 days',
            'user'               => $user,
        ];
    }

    public function revokeUserTokens(int $userId): void
    {
        RefreshToken::where('user_id', $userId)->update(['is_revoked' => true]);
    }
}
