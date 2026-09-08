<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected LoginService $loginService

    ) {}

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
}
