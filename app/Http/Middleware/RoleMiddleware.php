<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more comma-separated role names, e.g.:
     *   Route::middleware('role:HR,Owner')
     */
    public function handle(Request $request, Closure $next, string ...$roles): SymfonyResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $allowedRoles = array_map(
            static fn (string $r) => UserRole::from($r),
            $roles,
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
