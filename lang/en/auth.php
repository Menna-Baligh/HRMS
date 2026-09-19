<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'registered' => 'User registered successfully.',
    'otp_verified' => 'OTP verified successfully.',
    'email_not_found' => 'Email not found.',
    'invalid_otp' => 'OTP is not valid.',
    'invalid_or_expired_otp' => 'Invalid or expired OTP.',
    'password_reset' => 'Password reset successfully.',
    'otp_resent' => 'OTP resent successfully.',
    'otp_sent' => 'OTP sent successfully.',
    'validation' => [
        'name' => [
            'required' => 'The name field is required.',
            'string' => 'The name must be a string.',
            'min' => 'The name must be at least :min characters.',
            'max' => 'The name may not be greater than :max characters.',
        ],

        'email' => [
            'required' => 'The email field is required.',
            'email' => 'The email must be a valid email address.',
            'max' => 'The email may not be greater than :max characters.',
            'unique' => 'The email has already been taken.',
        ],

        'phone' => [
            'string' => 'The phone must be a string.',
            'max' => 'The phone may not be greater than :max characters.',
            'unique' => 'The phone has already been taken.',
        ],

        'password' => [
            'required' => 'The password field is required.',
            'confirmed' => 'The password confirmation does not match.',
        ],
    ],
];
