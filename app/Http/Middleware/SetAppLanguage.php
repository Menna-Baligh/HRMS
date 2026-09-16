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
        $locale = $request->query('lang') 
            ?? $request->query('locale') 
            ?? $request->header('Accept-Language', config('app.locale', 'en'));

        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
        } else {
            App::setLocale(config('app.locale', 'en')); 
        }

        return $next($request);
    }
}