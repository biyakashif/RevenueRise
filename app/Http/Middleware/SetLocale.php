<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class SetLocale
{
    protected $locales = ['en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi'];

    public function handle($request, Closure $next)
    {
        // First check URL parameter if it exists
        $locale = $request->input('locale');
        
        // If no URL parameter, check session
        if (!$locale || !in_array($locale, $this->locales)) {
            $locale = Session::get('locale');
        }
        
        // If no session, check cookie
        if (!$locale || !in_array($locale, $this->locales)) {
            $locale = $request->cookie('locale');
        }
        
        // If no cookie, check browser preference
        if (!$locale || !in_array($locale, $this->locales)) {
            $locale = $request->getPreferredLanguage($this->locales);
        }
        
        // Final fallback to English
        if (!$locale || !in_array($locale, $this->locales)) {
            $locale = 'en';
        }

        // Set the application locale
        App::setLocale($locale);
        
        // Always update session with current locale
        if (Session::get('locale') !== $locale) {
            Session::put('locale', $locale);
            Session::save();
        }

        return $next($request);
    }
}
