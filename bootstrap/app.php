<?php

use App\Helpers\ResponseHelper;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ForceJsonResponse::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (AuthenticationException $e, $request) {
            return ResponseHelper::error(
                message: __('Unauthenticated'),
                statusCode: Response::HTTP_UNAUTHORIZED
            );
        });
        $exceptions->render(function (UnauthorizedException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: 'You do not have the required role to perform this action.',
                    statusCode: Response::HTTP_FORBIDDEN
                );
            }
        });
        $exceptions->render(function (ValidationException $e, $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return ResponseHelper::error(
                message: $e->validator->errors()->first(),
                errors: $e->errors(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY 
            );
        }
    });
    })->create();
