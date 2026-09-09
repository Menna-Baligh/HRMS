<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\VerifyForgotPasswordOtpRequest;
use App\Services\Auth\ForgotPasswordService;
use App\Services\Auth\RegisterService;

class AuthController extends Controller
{
    public function __construct(
        protected RegisterService $registerService,
        protected ForgotPasswordService $forgotPasswordService,
         
    ) {}

    public function register(UserRegisterRequest $request)
    {
        $data = $this->registerService->register(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => $data,
        ], 201);
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        $data = $this->forgotPasswordService->sendOtp(
            $request->validated('email')
        );
        return response()->json([
            'success'=>'true',
            'message' => 'OTP sent successfully.',
            'data'=>$data
                ]);

    }

    public function verifyForgotPasswordOtp(VerifyForgotPasswordOtpRequest $request)
    {
        $data = $this->forgotPasswordService->verifyOtp(
            $request->validated('email'),
            $request->validated('otp')
        );

        return response()->json([
            'success'=>'true',
            'message'=>'OTP verified successfully',
            'data'=>$data
        ]);

    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->forgotPasswordService->resetPassword(
             $request->validated('reset_token'), 
             $request->validated('password')  
               );

        return response()->json([
            'success'=>'true',
            'message'=>'password reset successfully.'
        ]);
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        $data = $this->forgotPasswordService->resendOtp(
            $request->validated('email')
        );

        return response()->json([
            'success'=>'true',
            'message'=>'OTP resnd successfully',
            'data'=>$data
        ]);

    }

}
