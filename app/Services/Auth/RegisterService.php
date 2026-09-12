<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterService
{

    public function __construct( protected OtpService $otpService ) {
        
    }
    public function register(array $data): array
    {
        $user = User::create([

           'name' => $data['name'], 
           'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? null, 
            'provider' => $data['provider'] ?? null, 
            'provider_id' => $data['provider_id'] ?? null,
            'password' => Hash::make($data['password']), 
             'role' => 'Owner',
        ]);

        // Generate OTP and send it to the registered email
         $this->otpService->generate($user->email);

       // Generate JWT access token
        $token = JWTAuth::fromUser($user);
       return [
        'access_token' => $token,
         'token_type' => 'bearer', 
         'expires_in' => auth('api')->factory()->getTTL() / 60 . ' hours', 
         'user' => $user,
       ];

        // $otp = $this->otpService->generate($user->email);

        // return [
        //     'user' => $user,
        //     'otp' => $otp,
        // ];


        $otp = $this->otpService->generate($user->email); 
        return [ 
            'user' => $user, 
            'otp' => $otp, 
        ];

    }
}
