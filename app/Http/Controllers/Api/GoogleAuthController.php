<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleAuthService $googleAuthService
    ) {}

    public function redirect(): JsonResponse
    {
        try {
            $url = $this->googleAuthService->getGoogleRedirectUrl();

            return ResponseHelper::success(
                data: ['url' => $url],
                message: 'Google redirect URL generated successfully'
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Could not generate Google login URL',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function callback(): JsonResponse
    {
        try {
            $result = $this->googleAuthService->handleGoogleCallback();
            $result['user'] = new UserResource($result['user']);

            return ResponseHelper::success(
                data: $result,
                message: 'Logged in with Google successfully'
            );
        } catch (ValidationException $e) {
            return ResponseHelper::error(
                errors: $e->errors(),
                message: 'Google authentication failed',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Google authentication failed',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
