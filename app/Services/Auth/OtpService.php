<?php

namespace App\Services\Auth;

use Ichtrojan\Otp\Otp;

class OtpService
{
    protected Otp $otp;

    public function __construct()
    {
        $this->otp = new Otp;
    }

    public function generate(string $email): string
    {
        $response = $this->otp->generate(
            $email,
            'numeric',
            6,
            10
        );

        if (! $response->status) {
            throw new \RuntimeException(
                $response->message ?? 'Failed to generate OTP.'
            );
        }

        return $response->token;
    }

    public function verify(string $email, string $token): bool
    {
        $response = $this->otp->validate(
            $email,
            $token
        );

        return $response->status === true;
    }
}
