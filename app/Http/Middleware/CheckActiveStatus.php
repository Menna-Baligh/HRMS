<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if ($user && $user->employee && $user->employee->status === 'inactive') {
            auth('api')->logout();

            return ResponseHelper::error(
                message: 'Your account is inactive. Please activate your account first or contact your administrator.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
