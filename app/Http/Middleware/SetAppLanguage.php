<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAppLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        $locale = $request->query('lang')
            ?? $request->query('locale')
            ?? $request->header('Accept-Language')
            ?? $user?->locale
            ?? config('app.locale', 'en');

        $locale = in_array($locale, ['ar', 'en']) ? $locale : config('app.locale', 'en');

        App::setLocale($locale);

        if ($user && $user->locale !== $locale) {
            $user->update(['locale' => $locale]);
        }

        return $next($request);
    }
}
