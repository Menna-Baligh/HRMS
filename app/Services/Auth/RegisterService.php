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
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'avatar'      => $data['avatar'] ?? null,
            'provider'    => $data['provider'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'password'    => Hash::make($data['password']),
            'role' => 'Employee',
        ]);

        // $token = JWTAuth::fromUser($user);

        // return [
        //     'user' => $user,
        //     'token' => $token,
        //     'token_type' => 'bearer',
        // ];

        $otp = $this->otpService->generate($user->email); 
        return [ 
            'user' => $user, 
            'otp' => $otp, 
        ];
    }
}
