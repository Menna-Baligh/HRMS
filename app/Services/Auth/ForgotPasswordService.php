<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class ForgotPasswordService
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function sendOtp(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new RuntimeException('Email not found.');
        }
        $this->otpService->generate($user->email);

        return [
            'email' => $user->email,
        ];
    }

    public function verifyOtp($email, $otp)
    {

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new RuntimeException('Email not found.');
        }
        $isValid = $this->otpService->verify(
            $user->email,
            $otp
        );

        if (! $isValid) {
            throw new RuntimeException('Invalid or expired OTP.');
        }

        $resetToken = Str::random(64);
        Cache::put(
            'password_reset:'.$resetToken, $user->email, now()->addMinutes(10)
        );

        return [
            'email' => $user->email,
            'reset_token' => $resetToken,

        ];

    }

    public function resetPassword($resetToken, $password)
    {
        $key = 'password_reset:'.$resetToken;
        $email = Cache::get($key);

        if (! $email) {
            throw new RuntimeException('Invalid or expired reset token.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new RuntimeException('user not found.');
        }
        $user->update([
            'password' => $password,
        ]);

        // Token becomes unusable after password reset
        Cache::forget($key);

    }

    public function resendOtp($email)
    {
        return $this->sendOtp($email);

    }
}
