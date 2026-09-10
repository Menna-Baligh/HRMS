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
use App\Helpers\ResponseHelper;
// use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected RegisterService $registerService,
        protected ForgotPasswordService $forgotPasswordService,
       protected LoginService $loginService,
        protected LogoutService $logoutService
         
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

       

    

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginService->login($request->validated());
            $result['user'] = new UserResource($result['user']);

            return ResponseHelper::success(data: $result, message: 'Login successfully');
        } catch (ValidationException $e) {
            return ResponseHelper::error(
                errors: $e->errors(),
                message: 'Validation error',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(message: 'Something went wrong', statusCode: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->logoutService->logout();

            return ResponseHelper::success(message: 'Logged out successfully');
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Something went wrong',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
