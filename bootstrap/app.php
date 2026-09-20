<?php

use App\Helpers\ResponseHelper;
use App\Http\Middleware\CheckActiveStatus;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetAppLanguage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Http\Middleware\Authenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetAppLanguage::class);
        $middleware->append(ForceJsonResponse::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'check.active' => CheckActiveStatus::class,
            'jwt.auth' => Authenticate::class,
            'jwt.refresh' => RefreshToken::class,
            'set.app.language' => SetAppLanguage::class,
        ]);

        $middleware->api(append: [
            SetAppLanguage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // 1. Unauthenticated (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: __('auth.unauthenticated'),
                    statusCode: Response::HTTP_UNAUTHORIZED
                );
            }
        });

        // 2. Rate Limiting / Throttle Exception (429)
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $locale = $request->get('lang')
                    ?? $request->header('Accept-Language')
                    ?? config('app.locale');

                if (in_array($locale, ['ar', 'en'])) {
                    App::setLocale($locale);
                }

                $headers = $e->getHeaders();
                $retryAfter = $headers['Retry-After'] ?? 60;

                return ResponseHelper::error(
                    message: __('auth.throttle', ['seconds' => $retryAfter]),
                    statusCode: Response::HTTP_TOO_MANY_REQUESTS
                );
            }
        });

        // 3. Spatie Unauthorized Permission Exception (403)
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: __('auth.unauthorized_action'),
                    statusCode: Response::HTTP_FORBIDDEN
                );
            }
        });

        // 4. Laravel Authorization Exception / Policies (403)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $message = $e->getMessage() && $e->getMessage() !== 'This action is unauthorized.'
                    ? $e->getMessage()
                    : __('auth.unauthorized_action');

                return ResponseHelper::error(
                    message: $message,
                    statusCode: Response::HTTP_FORBIDDEN
                );
            }
        });

        // 5. Access Denied Exception (403)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: $e->getMessage() && $e->getMessage() !== 'This action is unauthorized.'
                        ? $e->getMessage()
                        : __('auth.unauthorized_action'),
                    statusCode: Response::HTTP_FORBIDDEN
                );
            }
        });

        // 6. Validation Exceptions (422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: __('auth.validation_error'),
                    errors: $e->errors(),
                    statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        });

        // 7. Resource Not Found (404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ResponseHelper::error(
                    message: __('auth.resource_not_found'),
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }
        });
    })->create();
