<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class Idempotent
{
    /**
     * Ponovljen zahtev sa istim zaglavljem Idempotency-Key ne izvršava se
     * ponovo, nego dobija sačuvan odgovor prvog zahteva (odeljak 6.3).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if ($key === null) {
            return $next($request);
        }
        if (! preg_match('/^[A-Za-z0-9-]{8,64}$/', $key)) {
            throw new BadRequestHttpException(
                'Zaglavlje Idempotency-Key nije ispravno.');
        }

        $client = $request->user()?->id ?? $request->ip();
        $cacheKey = 'idempotency:'.sha1("{$client} {$request->method()} {$request->path()} {$key}");
        $fingerprint = sha1($request->getContent());

        // Dva ista zahteva u istom trenutku: drugi čeka da prvi završi.
        $lock = Cache::lock("{$cacheKey}:lock", 10);
        if (! $lock->block(5)) {
            throw new ConflictHttpException(
                'Zahtev sa istim ključem se još obrađuje.');
        }

        try {
            $saved = Cache::get($cacheKey);
            if ($saved !== null) {
                if ($saved['fingerprint'] !== $fingerprint) {
                    throw new UnprocessableEntityHttpException(
                        'Ključ Idempotency-Key je već iskorišćen za drugačiji zahtev.');
                }

                return response($saved['body'], $saved['status'], [
                    ...$saved['headers'],
                    'Idempotent-Replayed' => 'true',
                ]);
            }

            $response = $next($request);

            // Pamte se samo uspešni odgovori; neuspeli zahtev nije ništa
            // promenio, pa sme da se ponovi.
            if ($response->isSuccessful()) {
                Cache::put($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'headers' => array_filter([
                        'Content-Type' => $response->headers->get('Content-Type'),
                        'Location' => $response->headers->get('Location'),
                    ]),
                    'body' => $response->getContent(),
                ], now()->addDay());
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
