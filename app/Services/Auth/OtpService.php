<?php

namespace App\Services\Auth;

use App\Mail\OtpMail;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpService
{
    protected Otp $otp;

    public function __construct()
    {
        $this->otp = new Otp;
    }

    /** * Generate a new OTP and send it to the given email. */
    public function generate(string $email): string
    {
        $response = $this->otp->generate($email, 'numeric', 6, 10);
        if (! $response->status) {
            throw ValidationException::withMessages([
                'email' => [__('auth.unable_to_generate_otp')],
            ]);
        } $otp = $response->token;
        // Send OTP email
        Mail::to($email)->queue(new OtpMail($otp));

        return $otp;
    }

    /** * Verify the given OTP for the email. */
    public function verify(string $email, string $token): bool
    {
        $response = $this->otp->validate($email, $token);
        if (! $response->status) {
            throw ValidationException::withMessages([
                'otp' => [__('auth.invalid_or_expired_otp')],
            ]);
        }

        return true;
    }

    /** * Generate a new OTP and send it to the given email. */

    /** * Verify the given OTP for the email. */
}
