<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->getLocale($request);
        
        if (in_array($locale, available_locales())) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    private function getLocale(Request $request): string
    {
        $availableLocales = available_locales();
        
        // 1. Session
        if ($sessionLocale = Session::get('locale')) {
            return $sessionLocale;
        }

        // 2. URL segment
        $urlSegment = $request->segment(1);
        if ($urlSegment && in_array($urlSegment, $availableLocales)) {
            return $urlSegment;
        }

        // 3. Browser headers
        $preferredLocale = $request->getPreferredLanguage($availableLocales);
        if ($preferredLocale) {
            return $preferredLocale;
        }

        // 4. Default
        return config('app.locale', 'fr');
    }
}