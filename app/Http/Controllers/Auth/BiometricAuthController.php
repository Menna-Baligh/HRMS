<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BiometricLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\BiometricAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BiometricAuthController extends Controller
{
    public function __construct(
        protected BiometricAuthService $biometricAuthService
    ) {}

    public function toggleBiometrics(Request $request): JsonResponse
    {
        try {
            $result = $this->biometricAuthService->toggleBiometrics($request->user());

            return ResponseHelper::success(
                data: [
                    'is_enabled' => $result['is_enabled'],
                    'biometric_token' => $result['biometric_token'],
                ],
                message: $result['message']
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: config('app.debug') ? $e->getMessage() : __('auth.failed'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function loginWithBiometrics(BiometricLoginRequest $request): JsonResponse
    {
        try {
            $data = $this->biometricAuthService->loginWithBiometrics(
                $request->validated('biometric_token')
            );
            $data['user'] = new UserResource($data['user']);

            return ResponseHelper::success(
                data: $data,
                message: __('auth.login_success')
            );
        } catch (Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : Response::HTTP_UNAUTHORIZED;

            return ResponseHelper::error(
                message: $e->getMessage() ?: __('auth.biometric_invalid_token'),
                statusCode: $statusCode
            );
        }
    }
}
