<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class RequireJsonBody
{
    /**
     * Zahtev sa telom mora da pošalje ispravan JSON. Inače bi Laravel
     * telo tiho odbacio, pa bi klijent dobio 422 "polje je obavezno"
     * umesto prave greške (415 ili 400).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $body = $request->getContent();

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && $body !== '') {
            if (! $request->isJson()) {
                throw new UnsupportedMediaTypeHttpException(
                    'Telo zahteva mora biti JSON (Content-Type: application/json).');
            }
            if (! json_validate($body)) {
                throw new BadRequestHttpException('Telo zahteva nije ispravan JSON.');
            }
        }

        return $next($request);
    }
}
