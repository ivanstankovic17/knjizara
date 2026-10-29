<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddResponseTime
{
    /**
     * Dodaje odgovoru zaglavlje X-Response-Time sa vremenom obrade zahteva.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        $ms = (hrtime(true) - $start) / 1e6;
        $response->headers->set(
            'X-Response-Time',
            number_format($ms, 1, '.', '').' ms',
        );

        return $response;
    }
}
