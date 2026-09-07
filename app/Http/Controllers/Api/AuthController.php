<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected LoginService $loginService

    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginService->login($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Login successfully',
            'data'    => [
                'access_token' => $result['access_token'],
                'token_type'   => $result['token_type'],
                'expires_in'   => $result['expires_in'],
                'user'         => new UserResource($result['user']),
            ],
        ], Response::HTTP_OK);
    }
}
