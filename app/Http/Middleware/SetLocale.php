<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Bira jezik aplikacije prema zaglavlju Accept-Language,
     * među jezicima navedenim kao parametri middleware-a.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$locales,
    ): Response {
        $locale = $request->getPreferredLanguage($locales);

        if ($locale !== null) {
            App::setLocale($locale);
        }

        $response = $next($request);
        $response->headers->set('Content-Language', App::getLocale());

        return $response;
    }
}
