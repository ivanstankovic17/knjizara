<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Kao Laravelov "verified", ali sa porukom na srpskom i bez
     * preusmeravanja, jer je ovo API.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasVerifiedEmail(), 403,
            'Potvrdite email adresu linkom koji smo vam poslali, pa pokušajte ponovo.');

        return $next($request);
    }
}
