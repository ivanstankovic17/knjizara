<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Svaki zahtev dobija identifikator, koji se upisuje uz svaku log
     * poruku, vraća u zaglavlju X-Request-Id i navodi u telu greške.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header('X-Request-Id');
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9-]{8,64}$/', $id)) {
            $id = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $id);
        Log::shareContext(['request_id' => $id]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
