<?php

namespace App\Services\Auth;

class LogoutService
{
    public function logout(): void
    {
        auth('api')->logout();
    }
}
