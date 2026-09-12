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
     * Accepts one or more comma-separated, pipe-separated, or vararg role names, e.g.:
     *   Route::middleware('role:HR,Owner')
     *   Route::middleware('role:Owner|HR')
     *   Route::middleware('role:Manager,HR,Owner')
     */
    public function handle(Request $request, Closure $next, string ...$roles): SymfonyResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        // Flatten any pipe-separated or comma-separated roles
        $allowedRoleNames = [];
        foreach ($roles as $roleGroup) {
            $split = preg_split('/[,|]/', $roleGroup);
            if ($split !== false) {
                foreach ($split as $role) {
                    $trimmed = trim($role);
                    if ($trimmed !== '') {
                        $allowedRoleNames[] = $trimmed;
                    }
                }
            }
        }

        // 1. Check using Spatie hasRole / hasAnyRole if available
        if (method_exists($user, 'hasAnyRole')) {
            try {
                if ($user->hasAnyRole($allowedRoleNames)) {
                    return $next($request);
                }
            } catch (\Throwable) {
                // In case Spatie roles table is empty or unmigrated, continue to check user->role
            }
        }

        // 2. Check using User->role property (enum or string)
        $userRoleValue = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        if (in_array($userRoleValue, $allowedRoleNames, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
    }
}
