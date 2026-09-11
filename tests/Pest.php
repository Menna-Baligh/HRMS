<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

use App\Models\User;
use Tymon\JWTAuth\JWTGuard;

/**
 * Generate a JWT authentication token for a user.
 */
function tokenFor(User $user): string
{
    /** @var JWTGuard $guard */
    $guard = auth('api');

    return $guard->login($user);
}
