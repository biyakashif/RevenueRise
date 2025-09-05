<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class SetLocale
{
    public function handle($request, Closure $next)
    {
        // Get locale from session or fallback to browser's preferred language or default to 'en'
        $locale = Session::get('locale', 
            $request->getPreferredLanguage(['en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi']) ?? 'en'
        );
        
        // Ensure the locale is valid, fallback to 'en' if not
        if (!in_array($locale, ['en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi'])) {
            $locale = 'en';
        }

        // Set the locale
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
